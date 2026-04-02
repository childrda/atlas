<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\AuthorizesStudentLearningSpace;
use App\Models\LearningSpace;
use App\Models\StudentSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    use AuthorizesStudentLearningSpace;

    public function start(LearningSpace $space): RedirectResponse
    {
        $this->authorizeStudentLearningSpace(auth()->user(), $space);

        abort_unless($space->is_published, 403, 'This space is not available.');
        abort_if($space->is_archived, 403, 'This space has been archived.');

        if ($space->opens_at && now()->lt($space->opens_at)) {
            return back()->with('error', 'This space is not open yet.');
        }

        if ($space->closes_at && now()->gt($space->closes_at)) {
            return back()->with('error', 'This space has closed.');
        }

        $session = StudentSession::firstOrCreate(
            [
                'student_id' => auth()->id(),
                'space_id' => $space->id,
                'status' => 'active',
            ],
            [
                'district_id' => auth()->user()->district_id,
                'started_at' => now(),
            ]
        );

        return redirect()->route('student.sessions.show', $session);
    }

    public function show(StudentSession $session): Response
    {
        abort_unless($session->student_id === auth()->id(), 403);

        $messages = $session->messages()
            ->whereIn('role', ['user', 'assistant', 'teacher_inject'])
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        return Inertia::render('Student/Session', [
            'session' => $session->load('space:id,title,description,bridger_tone,goals,max_messages'),
            'messages' => $messages,
        ]);
    }

    public function end(StudentSession $session): RedirectResponse
    {
        abort_unless($session->student_id === auth()->id(), 403);
        abort_unless($session->status === 'active', 422);

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Great work! Your session is complete.');
    }
}
