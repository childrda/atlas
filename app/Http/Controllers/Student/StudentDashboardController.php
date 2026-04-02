<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Student/Dashboard', [
            'user' => auth()->user()->load('school', 'district'),
        ]);
    }
}
