<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    Route::get('/auth/{provider}', [SocialiteController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.callback');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Teacher portal
// TODO: split district_admin and school_admin into /admin portal in Phase 8+
Route::middleware(['auth', 'role:teacher,school_admin,district_admin'])
    ->prefix('teach')
    ->name('teacher.')
    ->group(function () {
        Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');
        // Phases 2–7 add routes here
    });

// Student portal
Route::middleware(['auth', 'role:student'])
    ->prefix('learn')
    ->name('student.')
    ->group(function () {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
        // Phases 2–3 add routes here
    });

Route::get('/', function () {
    return redirect()->route('login');
});
