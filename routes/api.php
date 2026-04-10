<?php

use App\Http\Controllers\Api\v2\AnswerController;
use App\Http\Controllers\Api\v2\ArticleController;
use App\Http\Controllers\Api\v2\Auth\AuthController as V2AuthController;
use App\Http\Controllers\Api\v2\ContactController;
use App\Http\Controllers\Api\v2\EnrollmentController;
use App\Http\Controllers\Api\v2\ExamController;
use App\Http\Controllers\Api\v2\PracticeExamController;
use App\Http\Controllers\Api\v2\QuestionController;
use App\Http\Controllers\Api\v2\SettingController;
use App\Http\Controllers\Api\v2\StatsController;
use App\Http\Controllers\Api\v2\StudentController;
use App\Http\Controllers\Api\v2\StudentImportController;
use App\Http\Controllers\Api\v2\StudentStatsController;
use App\Http\Controllers\Api\v2\TrendingCourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| V2 API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v2')->name('api.v2.')->group(function () {
    // Auth Group
    Route::prefix('auth')->name('auth.')->group(function () {
        // Public routes
        Route::post('/username/check', [V2AuthController::class, 'checkUsername'])->name('username.check');
        Route::post('/register/student', [V2AuthController::class, 'registerStudent'])->name('register.student');
        Route::post('/register/parent', [V2AuthController::class, 'registerParent'])->name('register.parent');

        // Login flow
        Route::post('/login/check', [V2AuthController::class, 'loginCheck'])->name('login.check');
        Route::post('/login', [V2AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        // OTP (Rate Limited)
        Route::post('/otp/send', [V2AuthController::class, 'sendOtp'])
            ->middleware('throttle:otp-send')
            ->name('otp.send');
        Route::post('/otp/verify', [V2AuthController::class, 'verifyOtp'])
            ->middleware('throttle:otp-verify')
            ->name('otp.verify');

        // Password reset
        Route::post('/password/reset', [V2AuthController::class, 'resetPassword'])->name('password.reset');
        Route::post('/password/change', [V2AuthController::class, 'changePassword'])->name('password.change');

        // Authenticated routes
        Route::middleware('jwt')->group(function () {
            Route::get('/me', [V2AuthController::class, 'me'])->name('me');
            Route::post('/profile', [V2AuthController::class, 'updateProfile'])->name('profile.update');
            Route::post('/logout', [V2AuthController::class, 'logout'])->name('logout');

            // Device management
            Route::get('/devices', [V2AuthController::class, 'devices'])->name('devices.index');
            Route::post('/devices/{device}', [V2AuthController::class, 'revokeDevice'])->name('devices.revoke');
        });
    });

    // Exams
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::get('/{exam}', [ExamController::class, 'show'])->name('show');
        Route::get('/{exam}/sections', [ExamController::class, 'sections'])->name('sections');
    });

    // Questions
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/trending', [QuestionController::class, 'trending'])->name('trending');
        Route::get('/recent', [QuestionController::class, 'recent'])->name('recent');
        Route::get('/search', [QuestionController::class, 'search'])->name('search');
        Route::get('/{question}', [QuestionController::class, 'show'])->name('show');
    });

    // Category Questions
    Route::get('/categories/{category}/questions', [QuestionController::class, 'byCategory'])->name('categories.questions');

    // Category Articles
    Route::get('/categories/{category}/articles', [ArticleController::class, 'byCategory'])->name('categories.articles');

    // Articles
    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/{article}', [ArticleController::class, 'show'])->name('show');
        Route::get('/{article}/questions', [ArticleController::class, 'questions'])->name('questions');
    });

    // Practice Exams (Models)
    Route::prefix('practice-exams')->name('practice-exams.')->group(function () {
        Route::get('/', [PracticeExamController::class, 'index'])->name('index');
        Route::get('/{practiceExam}', [PracticeExamController::class, 'show'])->name('show');
    });

    // Contact Us
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/groups', [SettingController::class, 'groups'])->name('settings.groups');
    Route::get('/settings/{key}', [SettingController::class, 'show'])->name('settings.show');

    // =====================================================
    // Stats & Analytics (Authenticated)
    // =====================================================
    Route::middleware('jwt')->group(function () {
        // Platform Stats
        Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

        // Trending Courses
        Route::get('/trending-courses', [TrendingCourseController::class, 'index'])->name('trending-courses.index');

        // Student Stats (for charts)
        Route::get('/student-stats', [StudentStatsController::class, 'index'])->name('student-stats.index');

        // Student Management (CRUD)
        Route::apiResource('students', StudentController::class)->except(['create', 'store']);

        // Enrollments/Subscriptions List
        Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');

        // Student Import
        Route::prefix('students')->name('students.')->group(function () {
            Route::post('/', [StudentImportController::class, 'store'])->name('store');
            Route::post('/bulk', [StudentImportController::class, 'bulkStore'])->name('bulk');
            Route::post('/import', [StudentImportController::class, 'importFile'])->name('import');
        });

        // Question Answers
        Route::post('/questions/answer', [AnswerController::class, 'submit'])->name('questions.answer');
    });
});
