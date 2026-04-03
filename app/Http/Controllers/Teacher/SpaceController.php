<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\JoinCode;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LearningSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function index(): Response
    {
        $spaces = LearningSpace::where('teacher_id', auth()->id())
            ->where('is_archived', false)
            ->withCount('sessions')
            ->latest()
            ->paginate(20);

        return Inertia::render('Teacher/Spaces/Index', ['spaces' => $spaces]);
    }

    public function create(): Response
    {
        return Inertia::render('Teacher/Spaces/Create', [
            'classrooms' => Classroom::where('teacher_id', auth()->id())
                ->get(['id', 'name', 'grade_level', 'subject']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'subject' => 'nullable|string|max:100',
            'grade_level' => 'nullable|string|max:20',
            'classroom_id' => [
                'nullable',
                'uuid',
                Rule::exists('classrooms', 'id')->where('teacher_id', $request->user()->id),
            ],
            'system_prompt' => 'nullable|string|max:4000',
            'goals' => 'nullable|array|max:5',
            'goals.*' => 'string|max:200',
            'atlaas_tone' => 'in:encouraging,socratic,direct,playful',
            'language' => 'string|max:10',
            'max_messages' => 'nullable|integer|min:5|max:500',
        ]);

        $space = LearningSpace::create([
            ...$data,
            'district_id' => $request->user()->district_id,
            'teacher_id' => $request->user()->id,
            'goals' => $data['goals'] ?? [],
        ]);

        return redirect()->route('teacher.spaces.show', $space)
            ->with('success', 'Space created. Configure it before sharing the join code.');
    }

    public function show(LearningSpace $space): Response
    {
        $this->authorize('view', $space);

        return Inertia::render('Teacher/Spaces/Show', [
            'space' => $space->load('classroom'),
            'recentSessions' => $space->sessions()
                ->with('student:id,name')
                ->latest('started_at')
                ->limit(10)
                ->get(),
            'sessionCount' => $space->sessions()->count(),
        ]);
    }

    public function edit(LearningSpace $space): Response
    {
        $this->authorize('update', $space);

        return Inertia::render('Teacher/Spaces/Edit', [
            'space' => $space,
            'classrooms' => Classroom::where('teacher_id', auth()->id())
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, LearningSpace $space): RedirectResponse
    {
        $this->authorize('update', $space);

        $space->update($request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'subject' => 'nullable|string|max:100',
            'grade_level' => 'nullable|string|max:20',
            'classroom_id' => [
                'nullable',
                'uuid',
                Rule::exists('classrooms', 'id')->where('teacher_id', $request->user()->id),
            ],
            'system_prompt' => 'nullable|string|max:4000',
            'goals' => 'nullable|array|max:5',
            'goals.*' => 'string|max:200',
            'atlaas_tone' => 'in:encouraging,socratic,direct,playful',
            'language' => 'string|max:10',
            'max_messages' => 'nullable|integer|min:5|max:500',
        ]));

        return back()->with('success', 'Space updated.');
    }

    public function publish(Request $request, LearningSpace $space): RedirectResponse
    {
        $this->authorize('update', $space);

        $space->update(['is_published' => ! $space->is_published]);

        $label = $space->is_published ? 'published' : 'unpublished';

        return back()->with('success', "Space {$label}.");
    }

    public function duplicate(LearningSpace $space): RedirectResponse
    {
        $this->authorize('view', $space);

        $copy = $space->replicate(['join_code', 'is_public', 'classroom_id', 'is_published']);
        $copy->title = $space->title.' (Copy)';
        $copy->teacher_id = auth()->id();
        $copy->join_code = JoinCode::generate('learning_spaces');
        $copy->save();

        return redirect()->route('teacher.spaces.edit', $copy)
            ->with('success', 'Space duplicated. Edit it before publishing.');
    }

    public function destroy(LearningSpace $space): RedirectResponse
    {
        $this->authorize('delete', $space);
        $space->update(['is_archived' => true]);

        return redirect()->route('teacher.spaces.index')
            ->with('success', 'Space archived.');
    }
}
