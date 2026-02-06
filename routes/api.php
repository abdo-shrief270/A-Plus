<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\EnrollmentController;
use App\Http\Controllers\Api\v1\ExamController;
use App\Http\Controllers\Api\v1\HomeController;
use App\Http\Controllers\Api\v1\LessonController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('jwt')->get('/', function () {
        return response(['message' => 'Hello world!']);
    });

    Route::prefix('/')->group(function () {
        Route::post('/contact', [HomeController::class, 'contactUs']);
    });

    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::get('/categories/{category}', [ExamController::class, 'categoryData']);
        Route::get('/subjects/{subject}', [ExamController::class, 'subjectData']);
        Route::get('/data', [ExamController::class, 'categories'])->middleware('jwt');
    });

    Route::prefix('questions')->group(function () {
        Route::get('/{question}', [QuestionController::class, 'questionData']);
    });

    Route::middleware('jwt')->prefix('lessons')->group(function () {
        Route::get('/', [LessonController::class, 'index']);
        Route::get('/{lesson}', [LessonController::class, 'show']);
        Route::post('/{lesson}/progress', [LessonController::class, 'updateProgress']);
    });

    // Gamification
    Route::apiResource('leagues', \App\Http\Controllers\Api\v1\LeagueController::class);
    Route::get('leaderboard', [\App\Http\Controllers\Api\v1\LeaderboardController::class, 'index']);

    // Economy
    Route::post('payment/initiate', [\App\Http\Controllers\Api\v1\PaymentController::class, 'initiate']);
    Route::post('payment/callback', [\App\Http\Controllers\Api\v1\PaymentController::class, 'callback']); // Should be outside auth or handle auth checks manually if redirect

    // Answers & Revision
    Route::post('answers', [\App\Http\Controllers\Api\v1\AnswerController::class, 'submit']);
    Route::get('revision/stats', [\App\Http\Controllers\Api\v1\RevisionController::class, 'stats']);
    Route::get('revision/history', [\App\Http\Controllers\Api\v1\RevisionController::class, 'history']);

    Route::apiResource('questions', \App\Http\Controllers\Api\v1\QuestionController::class)->only(['show']);

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::prefix('username')->group(function () {
            Route::post('/check', [AuthController::class, 'checkUserName']);
        });


        Route::middleware('jwt')->prefix('user')->group(function () {
            Route::get('/', [AuthController::class, 'getUser']);
            Route::post('/update', [AuthController::class, 'updateUser']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::post('/checkOTP', [AuthController::class, 'checkOTP']);
        });

    });

    // Course Cycle Routes
    Route::middleware('jwt')->prefix('courses')->group(function () {
        Route::post('/{course}/enroll', [EnrollmentController::class, 'enroll']);
    });

    Route::middleware('jwt')->prefix('school')->group(function () {
        Route::post('/enroll-bulk', [EnrollmentController::class, 'bulkEnroll']);
    });

    Route::middleware('jwt')->prefix('payment')->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        // Webhook usually doesn't need JWT if verified by signature, but for now...
        Route::post('/webhook', [PaymentController::class, 'webhook']);
    });
});








Route::middleware('jwt')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'updateUser']);
});
