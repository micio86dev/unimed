<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated API
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('subjects', [SubjectController::class, 'index']);

    // Quiz catalogue (students see published quizzes; admins can pass ?scope=all).
    Route::get('quizzes', [QuizController::class, 'index']);
    Route::get('quizzes/{quiz}', [QuizController::class, 'show']);

    // Quiz attempts — the core student flow.
    Route::get('attempts', [AttemptController::class, 'index']);
    Route::post('attempts', [AttemptController::class, 'store']);
    Route::get('attempts/{attempt}', [AttemptController::class, 'show']);
    Route::patch('attempts/{attempt}/answers', [AttemptController::class, 'saveAnswer']);
    Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit']);
    Route::get('attempts/{attempt}/result', [AttemptController::class, 'result']);

    // Rankings.
    Route::get('rankings', [RankingController::class, 'index']);
    Route::get('rankings/me', [RankingController::class, 'me']);

    // Student analytics.
    Route::get('analytics/student', [AnalyticsController::class, 'student']);

    /*
    |----------------------------------------------------------------------
    | Admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::get('analytics', [AnalyticsController::class, 'admin']);

        Route::apiResource('questions', QuestionController::class);
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        // Quiz catalogue read endpoints are shared above; writes are admin-only.
        Route::post('quizzes', [QuizController::class, 'store']);
        Route::put('quizzes/{quiz}', [QuizController::class, 'update']);
        Route::patch('quizzes/{quiz}', [QuizController::class, 'update']);
        Route::delete('quizzes/{quiz}', [QuizController::class, 'destroy']);

        Route::post('uploads', [UploadController::class, 'store']);
    });
});
