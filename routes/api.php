<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Traits\ApiResponse;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/', function () {
        return response(['message' => 'Hello world!']);
    });


    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::prefix('username')->group(function () {
            Route::post('/check', [AuthController::class, 'checkUserName']);
        });
    });
});








Route::middleware('jwt')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'updateUser']);
});
