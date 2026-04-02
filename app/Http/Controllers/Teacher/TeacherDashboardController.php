<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Teacher/Dashboard', [
            'user' => auth()->user()->load('school', 'district'),
        ]);
    }
}
