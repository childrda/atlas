<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Student\MessageController;
use App\Http\Controllers\Student\SessionController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentJoinController;
use App\Http\Controllers\Student\StudentSpaceController;
use App\Http\Controllers\Student\TTSController;
use App\Http\Controllers\Teacher\AlertController;
use App\Http\Controllers\Teacher\ClassroomController;
use App\Http\Controllers\Teacher\CompassController;
use App\Http\Controllers\Teacher\DiscoverController;
use App\Http\Controllers\Teacher\SpaceController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\ToolkitController;
use Illuminate\Support\Facades\Route;
use OpenAI\Laravel\Facades\OpenAI;

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

        Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::patch('alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');

        Route::get('compass', [CompassController::class, 'index'])->name('compass.index');
        Route::get('compass/sessions/{session}', [CompassController::class, 'session'])->name('compass.session');
        Route::post('compass/sessions/{session}/inject', [CompassController::class, 'injectMessage'])->name('compass.inject');
        Route::post('compass/sessions/{session}/end', [CompassController::class, 'endSession'])->name('compass.end');

        Route::get('toolkit', [ToolkitController::class, 'index'])->name('toolkit.index');
        Route::get('toolkit/{tool:slug}', [ToolkitController::class, 'show'])->name('toolkit.show');
        Route::post('toolkit/{tool:slug}/run', [ToolkitController::class, 'run'])->name('toolkit.run');

        Route::get('discover', [DiscoverController::class, 'index'])->name('discover.index');
        Route::post('discover/{libraryItem}/import', [DiscoverController::class, 'import'])->name('discover.import');
        Route::post('discover/{libraryItem}/approve', [DiscoverController::class, 'approve'])->name('discover.approve');
        Route::post('discover/{libraryItem}/rate', [DiscoverController::class, 'rate'])->name('discover.rate');

        Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
        Route::patch('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
        Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
        Route::post('classrooms/{classroom}/students', [ClassroomController::class, 'addStudent'])->name('classrooms.students.add');
        Route::delete('classrooms/{classroom}/students/{student}', [ClassroomController::class, 'removeStudent'])->name('classrooms.students.remove');

        Route::get('spaces', [SpaceController::class, 'index'])->name('spaces.index');
        Route::get('spaces/create', [SpaceController::class, 'create'])->name('spaces.create');
        Route::post('spaces', [SpaceController::class, 'store'])->name('spaces.store');
        Route::get('spaces/{space}', [SpaceController::class, 'show'])->name('spaces.show');
        Route::get('spaces/{space}/edit', [SpaceController::class, 'edit'])->name('spaces.edit');
        Route::patch('spaces/{space}', [SpaceController::class, 'update'])->name('spaces.update');
        Route::post('spaces/{space}/publish', [SpaceController::class, 'publish'])->name('spaces.publish');
        Route::post('spaces/{space}/duplicate', [SpaceController::class, 'duplicate'])->name('spaces.duplicate');
        Route::delete('spaces/{space}', [SpaceController::class, 'destroy'])->name('spaces.destroy');
    });

// Student portal
Route::middleware(['auth', 'role:student'])
    ->prefix('learn')
    ->name('student.')
    ->group(function () {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('join', [StudentJoinController::class, 'show'])->name('join.show');
        Route::post('join', [StudentJoinController::class, 'join'])->name('join');
        Route::get('spaces', [StudentSpaceController::class, 'index'])->name('spaces.index');
        Route::get('spaces/{space}', [StudentSpaceController::class, 'show'])->name('spaces.show');
        Route::post('spaces/{space}/sessions', [SessionController::class, 'start'])->name('sessions.start');
        Route::get('sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
        Route::post('sessions/{session}/end', [SessionController::class, 'end'])->name('sessions.end');
        Route::post('sessions/{session}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('sessions/{session}/speak', [TTSController::class, 'speak'])
            ->middleware('throttle:20,1')
            ->name('sessions.speak');
    });

Route::get('/', function () {
    return redirect()->route('login');
});

if (app()->environment('local')) {
    Route::get('/test-llm', function () {
        $response = OpenAI::chat()->create([
            'model' => config('openai.model'),
            'messages' => [['role' => 'user', 'content' => 'Reply with exactly: ATLAAS LLM connected.']],
        ]);

        return $response->choices[0]->message->content;
    })->middleware(['auth', 'role:district_admin']);
}
