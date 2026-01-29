<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuestionController;
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
        Route::post('/{course}/enroll', [\App\Http\Controllers\Api\v1\EnrollmentController::class, 'enroll']);
    });

    Route::middleware('jwt')->prefix('school')->group(function () {
        Route::post('/enroll-bulk', [\App\Http\Controllers\Api\v1\EnrollmentController::class, 'bulkEnroll']);
    });

    Route::middleware('jwt')->prefix('payment')->group(function () {
        Route::post('/initiate', [\App\Http\Controllers\Api\v1\PaymentController::class, 'initiate']);
        // Webhook usually doesn't need JWT if verified by signature, but for now...
        Route::post('/webhook', [\App\Http\Controllers\Api\v1\PaymentController::class, 'webhook']);
    });
});








Route::middleware('jwt')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'updateUser']);
});
