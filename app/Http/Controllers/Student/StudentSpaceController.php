<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\AuthorizesStudentLearningSpace;
use App\Models\LearningSpace;
use App\Models\StudentSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentSpaceController extends Controller
{
    use AuthorizesStudentLearningSpace;

    public function index(): Response
    {
        $spaces = LearningSpace::withoutGlobalScope('district')
            ->where('learning_spaces.district_id', auth()->user()->district_id)
            ->where('is_published', true)
            ->where('is_archived', false)
            ->whereHas('classroom.students', fn ($q) => $q->where('users.id', auth()->id()))
            ->with('teacher:id,name')
            ->get();

        return Inertia::render('Student/Spaces/Index', ['spaces' => $spaces]);
    }

    public function show(Request $request, LearningSpace $space): Response
    {
        $this->authorizeStudentLearningSpace($request->user(), $space);

        $activeSession = StudentSession::where('student_id', $request->user()->id)
            ->where('space_id', $space->id)
            ->where('status', 'active')
            ->first();

        return Inertia::render('Student/Spaces/Show', [
            'space' => $space->load('teacher:id,name'),
            'activeSession' => $activeSession,
        ]);
    }
}
