<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningSpace;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentSpaceController extends Controller
{
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
        $this->authorizeStudentSpace($request->user(), $space);

        return Inertia::render('Student/Spaces/Show', [
            'space' => $space->load('teacher:id,name'),
        ]);
    }

    protected function authorizeStudentSpace(User $user, LearningSpace $space): void
    {
        abort_unless($space->district_id === $user->district_id, 403);
        abort_unless($space->is_published && ! $space->is_archived, 403);

        if ($space->classroom_id) {
            abort_unless(
                $space->classroom->students()->where('users.id', $user->id)->exists(),
                403
            );
        }
    }
}
