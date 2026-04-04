<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningSpace;
use App\Models\SafetyAlert;
use App\Models\StudentSession;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    public function index(): Response
    {
        $teacherId = auth()->id();

        $activeSpaces = LearningSpace::query()
            ->where('teacher_id', $teacherId)
            ->where('is_archived', false)
            ->count();

        $activeStudents = (int) StudentSession::query()
            ->where('status', 'active')
            ->whereHas('space', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('is_archived', false);
            })
            ->distinct()
            ->count('student_id');

        $openAlerts = SafetyAlert::query()
            ->where('teacher_id', $teacherId)
            ->where('status', 'open')
            ->count();

        return Inertia::render('Teacher/Dashboard', [
            'user' => auth()->user()->load('school', 'district'),
            'stats' => [
                'active_spaces' => $activeSpaces,
                'active_students' => $activeStudents,
                'open_alerts' => $openAlerts,
            ],
        ]);
    }
}
