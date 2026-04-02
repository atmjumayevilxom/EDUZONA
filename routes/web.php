<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public session page — o'quvchilar akkountsiz kiradi
Route::get('/session/{code}', function ($code) {
    return Inertia::render('Session/Show', ['sessionCode' => $code]);
})->name('session.show');

// Public student cabinet — natijalar tarixi
Route::get('/student', function () {
    return Inertia::render('Student/Dashboard');
})->name('student.dashboard');

// Public certificate page
Route::get('/certificate', function () {
    return Inertia::render('Certificate');
})->name('certificate');

// Public classroom join page
Route::get('/join/{code}', function ($code) {
    return Inertia::render('Classrooms/Join', ['joinCode' => strtoupper($code)]);
})->name('classroom.join');

// Login — required by Laravel auth middleware; redirect unauthenticated users to welcome page
Route::get('/login', function () {
    return redirect('/');
})->name('login');

// Google OAuth
Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Logout
Route::post('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

// Protected user routes
Route::middleware(['auth', 'check.status'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/create', function () {
        return Inertia::render('CreateHub');
    })->name('create.hub');

    Route::get('/lessons', function () {
        return Inertia::render('Lessons');
    })->name('lessons');

    Route::get('/library', function () {
        return Inertia::render('Library');
    })->name('library');

    Route::get('/materials', function () {
        return Inertia::render('Materials');
    })->name('materials');

    Route::get('/help', function () {
        return Inertia::render('Help');
    })->name('help');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/classrooms', function () {
        return Inertia::render('Classrooms/Index');
    })->name('classrooms.index');

    Route::get('/games/create', function () {
        return Inertia::render('Games/Create');
    })->name('games.create');

    Route::get('/games/{id}', function ($id) {
        return Inertia::render('Games/Show', ['gameId' => (int)$id]);
    })->name('games.show');

    Route::get('/games/{id}/play', function ($id) {
        return Inertia::render('Games/Play', ['gameId' => (int)$id]);
    })->name('games.play');

    Route::get('/games/{id}/stats', function ($id) {
        return Inertia::render('Games/Stats', ['gameId' => (int)$id]);
    })->name('games.stats');

    Route::get('/games/{id}/print', function ($id) {
        return Inertia::render('Games/Print', ['gameId' => (int)$id]);
    })->name('games.print');

    // AI Video Yechim
    Route::get('/ai-video', function () {
        return Inertia::render('AiVideo/Index');
    })->name('ai-video.index');

    Route::get('/ai-video/history', function () {
        return Inertia::render('AiVideo/History');
    })->name('ai-video.history');
});

// Admin login (parol bilan)
Route::get('/admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->middleware('auth')->name('admin.logout');

// Admin routes
Route::middleware(['auth', 'check.status', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/users', function () {
        return Inertia::render('Admin/Users');
    })->name('users');

    Route::get('/templates', function () {
        return Inertia::render('Admin/Templates');
    })->name('templates');

    Route::get('/games', function () {
        return Inertia::render('Admin/Games');
    })->name('games');

    Route::get('/categories', function () {
        return Inertia::render('Admin/Categories');
    })->name('categories');

    Route::get('/ai-settings', function () {
        return Inertia::render('Admin/AiSettings');
    })->name('ai-settings');

    Route::get('/audit-logs', function () {
        return Inertia::render('Admin/AuditLogs');
    })->name('audit-logs');

    Route::get('/prompts', function () {
        return Inertia::render('Admin/Prompts');
    })->name('prompts');

    Route::get('/video-settings', function () {
        return Inertia::render('Admin/VideoSettings');
    })->name('video-settings');
});
