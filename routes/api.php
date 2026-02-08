<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\EnrollmentController;
use App\Http\Controllers\Api\v1\ExamController;
use App\Http\Controllers\Api\v1\HomeController;
use App\Http\Controllers\Api\v1\LessonController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\QuestionController;
use App\Http\Controllers\Api\v2\Auth\AuthController as V2AuthController;
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
            Route::put('/profile', [V2AuthController::class, 'updateProfile'])->name('profile.update');
            Route::post('/logout', [V2AuthController::class, 'logout'])->name('logout');

            // Device management
            Route::get('/devices', [V2AuthController::class, 'devices'])->name('devices.index');
            Route::delete('/devices/{device}', [V2AuthController::class, 'revokeDevice'])->name('devices.revoke');
        });
    });

    // Exams
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v2\ExamController::class, 'index'])->name('index');
        Route::get('/{exam}', [\App\Http\Controllers\Api\v2\ExamController::class, 'show'])->name('show');
        Route::get('/{exam}/subjects', [\App\Http\Controllers\Api\v2\ExamController::class, 'subjects'])->name('subjects');
        Route::get('/{exam}/sections', [\App\Http\Controllers\Api\v2\ExamController::class, 'sections'])->name('sections');
    });

    // Questions
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/trending', [\App\Http\Controllers\Api\v2\QuestionController::class, 'trending'])->name('trending');
        Route::get('/recent', [\App\Http\Controllers\Api\v2\QuestionController::class, 'recent'])->name('recent');
        Route::get('/search', [\App\Http\Controllers\Api\v2\QuestionController::class, 'search'])->name('search');
        Route::get('/{question}', [\App\Http\Controllers\Api\v2\QuestionController::class, 'show'])->name('show');
    });

    // Subject & Category Questions
    Route::get('/subjects/{subject}/questions', [\App\Http\Controllers\Api\v2\QuestionController::class, 'bySubject'])->name('subjects.questions');
    Route::get('/categories/{category}/questions', [\App\Http\Controllers\Api\v2\QuestionController::class, 'byCategory'])->name('categories.questions');

    // Practice Exams (Models)
    Route::prefix('practice-exams')->name('practice-exams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v2\PracticeExamController::class, 'index'])->name('index');
        Route::get('/{practiceExam}', [\App\Http\Controllers\Api\v2\PracticeExamController::class, 'show'])->name('show');
    });

    // Contact Us
    Route::post('/contact', [\App\Http\Controllers\Api\v2\ContactController::class, 'store'])->name('contact.store');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\v2\SettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/groups', [\App\Http\Controllers\Api\v2\SettingController::class, 'groups'])->name('settings.groups');
    Route::get('/settings/{key}', [\App\Http\Controllers\Api\v2\SettingController::class, 'show'])->name('settings.show');

    // =====================================================
    // Stats & Analytics (Authenticated)
    // =====================================================
    Route::middleware('jwt')->group(function () {
        // Platform Stats
        Route::get('/stats', [\App\Http\Controllers\Api\v2\StatsController::class, 'index'])->name('stats.index');

        // Trending Courses
        Route::get('/trending-courses', [\App\Http\Controllers\Api\v2\TrendingCourseController::class, 'index'])->name('trending-courses.index');

        // Student Stats (for charts)
        Route::get('/student-stats', [\App\Http\Controllers\Api\v2\StudentStatsController::class, 'index'])->name('student-stats.index');

        // Student Management (CRUD)
        Route::apiResource('students', \App\Http\Controllers\Api\v2\StudentController::class)->except(['create', 'store']);

        // Student Import
        Route::prefix('students')->name('students.')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\v2\StudentImportController::class, 'store'])->name('store');
            Route::post('/bulk', [\App\Http\Controllers\Api\v2\StudentImportController::class, 'bulkStore'])->name('bulk');
            Route::post('/import', [\App\Http\Controllers\Api\v2\StudentImportController::class, 'importFile'])->name('import');
        });
    });
});

// /*
// |--------------------------------------------------------------------------
// | V1 API Routes
// |--------------------------------------------------------------------------
// */
// Route::prefix('v1')->name('api.v1.')->group(function () {
//     Route::middleware('jwt')->get('/', function () {
//         return response(['message' => 'Hello world!']);
//     });

//     Route::prefix('/')->group(function () {
//         Route::post('/contact', [HomeController::class, 'contactUs']);
//     });

//     Route::prefix('exams')->group(function () {
//         Route::get('/', [ExamController::class, 'index']);
//         Route::get('/categories/{category}', [ExamController::class, 'categoryData']);
//         Route::get('/subjects/{subject}', [ExamController::class, 'subjectData']);
//         Route::get('/data', [ExamController::class, 'categories'])->middleware('jwt');
//     });

//     Route::prefix('questions')->group(function () {
//         Route::get('/{question}', [QuestionController::class, 'questionData']);
//     });

//     Route::middleware('jwt')->prefix('lessons')->group(function () {
//         Route::get('/', [LessonController::class, 'index']);
//         Route::get('/{lesson}', [LessonController::class, 'show']);
//         Route::post('/{lesson}/progress', [LessonController::class, 'updateProgress']);
//     });

//     // Gamification
//     Route::apiResource('leagues', \App\Http\Controllers\Api\v1\LeagueController::class);
//     Route::get('leaderboard', [\App\Http\Controllers\Api\v1\LeaderboardController::class, 'index']);

//     // Economy
//     Route::post('payment/initiate', [\App\Http\Controllers\Api\v1\PaymentController::class, 'initiate']);
//     Route::post('payment/callback', [\App\Http\Controllers\Api\v1\PaymentController::class, 'callback']); // Should be outside auth or handle auth checks manually if redirect

//     // Answers & Revision
//     Route::post('answers', [\App\Http\Controllers\Api\v1\AnswerController::class, 'submit']);
//     Route::get('revision/stats', [\App\Http\Controllers\Api\v1\RevisionController::class, 'stats']);
//     Route::get('revision/history', [\App\Http\Controllers\Api\v1\RevisionController::class, 'history']);

//     Route::apiResource('questions', \App\Http\Controllers\Api\v1\QuestionController::class)->only(['show']);

//     Route::prefix('auth')->group(function () {
//         Route::post('/register', [AuthController::class, 'register']);
//         Route::post('/login', [AuthController::class, 'login']);
//         Route::prefix('username')->group(function () {
//             Route::post('/check', [AuthController::class, 'checkUserName']);
//         });


//         Route::prefix('user')->group(function () {
//             Route::get('/', [AuthController::class, 'getUser'])->middleware('jwt');
//             Route::post('/update', [AuthController::class, 'updateUser'])->middleware('jwt');
//             Route::post('/reset-password', [AuthController::class, 'resetPassword']);
//             Route::post('/change-password', [AuthController::class, 'changePassword']);
//             Route::post('/checkOTP', [AuthController::class, 'checkOTP']);
//         });

//     });

//     // Course Cycle Routes
//     Route::middleware('jwt')->prefix('courses')->group(function () {
//         Route::post('/{course}/enroll', [EnrollmentController::class, 'enroll']);
//     });

//     Route::middleware('jwt')->prefix('school')->group(function () {
//         Route::post('/enroll-bulk', [EnrollmentController::class, 'bulkEnroll']);
//     });

//     Route::middleware('jwt')->prefix('payment')->group(function () {
//         Route::post('/initiate', [PaymentController::class, 'initiate']);
//         // Webhook usually doesn't need JWT if verified by signature, but for now...
//         Route::post('/webhook', [PaymentController::class, 'webhook']);
//     });
// });








// Route::middleware('jwt')->group(function () {
//     Route::get('/user', [AuthController::class, 'getUser']);
//     Route::post('/logout', [AuthController::class, 'logout']);
//     Route::put('/user', [AuthController::class, 'updateUser']);
// });
