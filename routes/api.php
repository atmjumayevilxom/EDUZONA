<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AiVideoController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

// ── System health check (public, throttled) ──────────────────────────────────
// Queue worker ishlayotganini tekshiradi. Frontend submit oldidan chaqiradi.
Route::get('/system/health', function () {
    $pending      = \DB::table('jobs')->count();
    $failedRecent = \DB::table('failed_jobs')
        ->where('failed_at', '>=', now()->subHour()->timestamp)
        ->count();
    $lastProcessed = \Illuminate\Support\Facades\Cache::get('queue_last_processed');
    $queueHealthy  = $lastProcessed !== null
        && \Carbon\Carbon::parse($lastProcessed)->diffInMinutes(now()) < 5;

    return response()->json([
        'queue_healthy'     => $queueHealthy,
        'pending_jobs'      => $pending,
        'failed_last_hour'  => $failedRecent,
        'last_processed_at' => $lastProcessed,
        'checked_at'        => now()->toIso8601String(),
    ]);
})->middleware('throttle:30,1');

// Public library (no auth)
Route::get('/public/games', [GameController::class, 'publicGames']);

// Public session routes (no auth — o'quvchilar sessiya kodi orqali kiradi)
Route::get('/sessions/{code}', [SessionController::class, 'show']);
Route::post('/sessions/{code}/results', [SessionController::class, 'submitResult'])->middleware('throttle:5,1');
Route::get('/sessions/{code}/results', [SessionController::class, 'results']);

// Auth: end session (only game owner)
Route::patch('/sessions/{code}/end', [SessionController::class, 'endSession'])->middleware(['auth', 'check.status']);

// Public student results lookup (name-based)
Route::get('/student/results', [StudentController::class, 'results'])->middleware('throttle:20,1');

// Public classroom join (no auth — student joins class)
Route::get('/classrooms/join/{code}', [ClassroomController::class, 'byCode']);
Route::post('/classrooms/join/{code}', [ClassroomController::class, 'join'])->middleware('throttle:10,1');

// Authenticated user routes
Route::middleware(['auth', 'check.status'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/templates', [TemplateController::class, 'index']);

    // Classroom management
    Route::get('/classrooms', [ClassroomController::class, 'index']);
    Route::post('/classrooms', [ClassroomController::class, 'store']);
    Route::delete('/classrooms/{id}', [ClassroomController::class, 'destroy']);
    Route::get('/classrooms/{id}/students', [ClassroomController::class, 'students']);
    Route::delete('/classrooms/{id}/students/{studentId}', [ClassroomController::class, 'removeStudent']);

    Route::get('/profile/stats', [GameController::class, 'profileStats']);
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{id}', [GameController::class, 'show']);
    Route::post('/games/generate', [GameController::class, 'generate'])->middleware('throttle:10,1');

    // ── AI Video Yechim ───────────────────────────────────────────────────────
    Route::post('/ai-video/generate', [AiVideoController::class, 'generate'])->middleware('throttle:10,1');
    Route::get('/ai-video/history',   [AiVideoController::class, 'history']);
    Route::get('/ai-video/{id}',      [AiVideoController::class, 'show']);
    Route::get('/ai-video/{id}/status', [AiVideoController::class, 'status']);
    Route::post('/games/{id}/session/create', [GameController::class, 'createSession']);
    Route::get('/games/{id}/stats', [GameController::class, 'stats']);
    Route::get('/games/{id}/stats/export', [GameController::class, 'exportStats']);
    Route::post('/games/{id}/copy', [GameController::class, 'copy']);
    Route::post('/games/{id}/retry', [GameController::class, 'retry']);
    Route::patch('/games/{id}/toggle-public', [GameController::class, 'togglePublic']);
    Route::delete('/games/{id}', [GameController::class, 'destroy']);
});

// Admin routes
Route::middleware(['auth', 'check.status', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [Admin\UserController::class, 'index']);
    Route::patch('/users/{id}/block', [Admin\UserController::class, 'block']);
    Route::patch('/users/{id}/activate', [Admin\UserController::class, 'activate']);
    Route::patch('/users/{id}/reset-limit', [Admin\UserController::class, 'resetDailyLimit']);

    Route::get('/templates', [Admin\TemplateController::class, 'index']);
    Route::post('/templates', [Admin\TemplateController::class, 'store']);
    Route::patch('/templates/{id}/toggle', [Admin\TemplateController::class, 'toggle']);
    Route::patch('/templates/{id}/budget', [Admin\TemplateController::class, 'updateBudget']);

    Route::get('/games', [Admin\GameController::class, 'index']);
    Route::delete('/games/{id}', [Admin\GameController::class, 'destroy']);

    Route::get('/categories', [Admin\CategoryController::class, 'index']);
    Route::post('/categories', [Admin\CategoryController::class, 'store']);
    Route::patch('/categories/{id}', [Admin\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [Admin\CategoryController::class, 'destroy']);

    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index']);

    Route::get('/ai-usage', [Admin\AiController::class, 'usage']);
    Route::patch('/ai-settings', [Admin\AiController::class, 'updateSettings']);

    Route::get('/prompts', [Admin\PromptController::class, 'index']);
    Route::post('/prompts', [Admin\PromptController::class, 'store']);
    Route::get('/prompts/{id}', [Admin\PromptController::class, 'show']);
    Route::patch('/prompts/{id}', [Admin\PromptController::class, 'update']);
    Route::patch('/prompts/{id}/activate', [Admin\PromptController::class, 'activate']);

    Route::get('/video-settings', [Admin\AiController::class, 'videoSettings']);
    Route::patch('/video-settings', [Admin\AiController::class, 'updateVideoSettings']);
});
