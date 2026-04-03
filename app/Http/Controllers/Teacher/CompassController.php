<?php

namespace App\Http\Controllers\Teacher;

use App\Events\MessageSent;
use App\Events\SessionEnded;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SafetyAlert;
use App\Models\StudentSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompassController extends Controller
{
    public function index(): Response
    {
        $activeSessions = StudentSession::query()
            ->whereHas('space', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->where('status', 'active')
            ->with(['student:id,name', 'space:id,title'])
            ->get()
            ->map(fn (StudentSession $s) => [
                'session_id' => $s->id,
                'student_id' => $s->student_id,
                'student_name' => $s->student->name,
                'space_id' => $s->space_id,
                'space_title' => $s->space->title,
                'started_at' => $s->started_at?->toISOString(),
                'message_count' => $s->message_count,
                'status' => $s->status,
                'last_message' => null,
                'last_activity_at' => $s->started_at?->toISOString(),
            ]);

        $openAlerts = SafetyAlert::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'open')
            ->with(['student:id,name', 'session.space:id,title'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Teacher/Compass/Index', [
            'initialSessions' => $activeSessions,
            'openAlerts' => $openAlerts,
            'teacherId' => auth()->id(),
        ]);
    }

    public function session(StudentSession $session): Response
    {
        $this->authorizeSession($session);

        return Inertia::render('Teacher/Compass/SessionDetail', [
            'session' => $session->load(['student:id,name', 'space:id,title']),
            'messages' => $session->messages()->orderBy('created_at')->get(),
        ]);
    }

    public function injectMessage(Request $request, StudentSession $session): RedirectResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->status === 'active', 422, 'Session is not active.');

        $data = $request->validate(['content' => 'required|string|max:500']);

        Message::create([
            'session_id' => $session->id,
            'district_id' => $session->district_id,
            'role' => 'teacher_inject',
            'content' => $data['content'],
        ]);

        $session->increment('message_count');
        $session->refresh();
        $session->load(['student', 'space']);
        MessageSent::dispatch($session, '(Teacher) '.substr($data['content'], 0, 70));

        return back()->with('success', 'Message sent to student.');
    }

    public function endSession(StudentSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        $session->update(['status' => 'abandoned', 'ended_at' => now()]);
        $session->load(['student', 'space']);
        SessionEnded::dispatch($session);

        return back()->with('success', 'Session ended.');
    }

    private function authorizeSession(StudentSession $session): void
    {
        $session->loadMissing('space');

        $isTeacherOwner = $session->space->teacher_id === auth()->id();
        $isAdmin = auth()->user()->hasRole(['school_admin', 'district_admin']);

        abort_unless($isTeacherOwner || $isAdmin, 403);
    }
}
