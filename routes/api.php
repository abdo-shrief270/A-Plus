<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
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
        Route::get('/{exam}', [ExamController::class, 'categories']);
    });

//    Route::prefix('questions')->group(function () {
//        Route::get('/{question}', [QuestionController::class, 'questionData']);
//    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::prefix('username')->group(function () {
            Route::post('/check', [AuthController::class, 'checkUserName']);
        });
        Route::prefix('user')->group(function () {
            Route::get('/', [AuthController::class, 'getUser']);
            Route::post('/update', [AuthController::class, 'updateUser']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::post('/checkOTP', [AuthController::class, 'checkOTP']);
        });

    });
});








Route::middleware('jwt')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'updateUser']);
});
