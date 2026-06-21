<?php

use App\Http\Controllers\Api\v2\AnswerController;
use App\Http\Controllers\Api\v2\ArticleController;
use App\Http\Controllers\Api\v2\BookmarkController;
use App\Http\Controllers\Api\v2\RevisionController;
use App\Http\Controllers\Api\v2\Auth\AuthController as V2AuthController;
use App\Http\Controllers\Api\v2\Auth\SecurityController;
use App\Http\Controllers\Api\v2\ContactController;
use App\Http\Controllers\Api\v2\CourseController;
use App\Http\Controllers\Api\v2\DashboardController;
use App\Http\Controllers\Api\v2\EnrollmentController;
use App\Http\Controllers\Api\v2\NotificationController;
use App\Http\Controllers\Api\v2\PageController;
use App\Http\Controllers\Api\v2\PaymentController;
use App\Http\Controllers\Api\v2\PlanController;
use App\Http\Controllers\Api\v2\SubscriptionController;
use App\Http\Controllers\Api\v2\TicketController;
use App\Http\Controllers\Api\v2\ExamController;
use App\Http\Controllers\Api\v2\PracticeExamController;
use App\Http\Controllers\Api\v2\QuestionController;
use App\Http\Controllers\Api\v2\QuizController;
use App\Http\Controllers\Api\v2\LeaderboardController;
use App\Http\Controllers\Api\v2\DailyChallengeController;
use App\Http\Controllers\Api\v2\ExamSimulationController;
use App\Http\Controllers\Api\v2\StudyPlanController;
use App\Http\Controllers\Api\v2\LessonController;
use App\Http\Controllers\Api\v2\ReviewController;
use App\Http\Controllers\Api\v2\PerformanceController;
use App\Http\Controllers\Api\v2\ParentDigestController;
use App\Http\Controllers\Api\v2\ChallengeController;
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
        Route::middleware(['jwt', 'single-device'])->group(function () {
            Route::get('/me', [V2AuthController::class, 'me'])->name('me');
            Route::post('/profile', [V2AuthController::class, 'updateProfile'])->name('profile.update');
            Route::post('/logout', [V2AuthController::class, 'logout'])->name('logout');

            // Device management
            Route::get('/devices', [V2AuthController::class, 'devices'])->name('devices.index');
            Route::post('/devices/{device}', [V2AuthController::class, 'revokeDevice'])->name('devices.revoke');

            // Security: 2FA + email/phone verification
            Route::prefix('security')->name('security.')->group(function () {
                Route::get('/', [SecurityController::class, 'status'])->name('status');
                Route::post('/otp', [SecurityController::class, 'sendOtp'])
                    ->middleware('throttle:otp-send')
                    ->name('otp');
                Route::post('/confirm', [SecurityController::class, 'confirm'])
                    ->middleware('throttle:otp-verify')
                    ->name('confirm');
            });
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
        Route::get('/{question}/correct-answer', [QuestionController::class, 'correctAnswer'])->name('correct-answer');
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

    // CMS Pages (about / terms / privacy ...)
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/{slug}', [PageController::class, 'show'])->name('show');
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/groups', [SettingController::class, 'groups'])->name('settings.groups');
    Route::get('/settings/{key}', [SettingController::class, 'show'])->name('settings.show');

    // =====================================================
    // Stats & Analytics (Authenticated)
    // =====================================================
    Route::middleware(['jwt', 'single-device'])->group(function () {
        // Dashboard bundle (stats + recent + trending in one call)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // Platform Stats
        Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

        // Trending Courses
        Route::get('/trending-courses', [TrendingCourseController::class, 'index'])->name('trending-courses.index');

        // Student Stats (for charts)
        Route::get('/student-stats', [StudentStatsController::class, 'index'])->name('student-stats.index');

        // Student Management (CRUD)
        Route::apiResource('students', StudentController::class)->except(['create', 'store']);

        // Courses
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

        // Enrollments (course-level)
        Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');

        // Plans (الباقات)
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');

        // Subscriptions (plan-level)
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');

        // Notifications (الإشعارات)
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('readAll');
            Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        });

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
            Route::post('/{payment}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
            Route::post('/{payment}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        });

        // Support tickets / رسائل التواصل
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/replies', [TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
        });

        // Student Import
        Route::prefix('students')->name('students.')->group(function () {
            Route::post('/', [StudentImportController::class, 'store'])->name('store');
            Route::post('/bulk', [StudentImportController::class, 'bulkStore'])->name('bulk');
            Route::post('/import', [StudentImportController::class, 'importFile'])->name('import');
        });

        // Question Answers
        Route::post('/questions/answer', [AnswerController::class, 'submit'])->name('questions.answer');

        // AI explanation (شرح بالذكاء الاصطناعي)
        Route::post('/questions/{question}/ai-explanation', [QuestionController::class, 'aiExplanation'])
            ->middleware('throttle:quiz-mutate')->name('questions.ai-explanation');

        // Bookmarks
        Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
            Route::get('/', [BookmarkController::class, 'index'])->name('index');
        });
        Route::post('/questions/{question}/bookmark', [BookmarkController::class, 'toggle'])->name('questions.bookmark.toggle');
        Route::delete('/questions/{question}/bookmark', [BookmarkController::class, 'destroy'])->name('questions.bookmark.destroy');

        // Revision metrics
        Route::get('/revision/metrics', [RevisionController::class, 'metrics'])->name('revision.metrics');

        // Smart Review (المراجعة الذكية)
        Route::get('/review/queue', [ReviewController::class, 'queue'])
            ->middleware('throttle:quiz-read')->name('review.queue');

        // Performance analytics (تحليل الأداء)
        Route::get('/performance', [PerformanceController::class, 'index'])
            ->middleware('throttle:quiz-read')->name('performance.index');

        // Parent weekly digest (ملخص ولي الأمر)
        Route::get('/parent/weekly-summary', [ParentDigestController::class, 'weekly'])
            ->middleware('throttle:quiz-read')->name('parent.weekly-summary');

        // Daily Challenge (التحدي اليومي)
        Route::get('/daily-challenge', [DailyChallengeController::class, 'show'])
            ->middleware('throttle:quiz-read')->name('daily-challenge.show');
        Route::post('/daily-challenge', [DailyChallengeController::class, 'start'])
            ->middleware('throttle:quiz-mutate')->name('daily-challenge.start');

        // Exam Simulation (محاكاة الاختبار)
        Route::get('/exam-simulation', [ExamSimulationController::class, 'show'])
            ->middleware('throttle:quiz-read')->name('exam-simulation.show');
        Route::post('/exam-simulation', [ExamSimulationController::class, 'start'])
            ->middleware('throttle:quiz-mutate')->name('exam-simulation.start');

        // Study Plan & Lessons (الخطة الدراسية والدروس)
        Route::get('/study-plan', [StudyPlanController::class, 'show'])
            ->middleware('throttle:quiz-read')->name('study-plan.show');
        Route::post('/study-plan/regenerate', [StudyPlanController::class, 'regenerate'])
            ->middleware('throttle:quiz-mutate')->name('study-plan.regenerate');
        Route::get('/lessons/{lesson}', [LessonController::class, 'show'])
            ->middleware('throttle:quiz-read')->name('lessons.show');
        Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete'])
            ->middleware('throttle:quiz-mutate')->name('lessons.complete');

        // Run a practice-exam model (نموذج) as a timed exam simulation
        Route::post('/practice-exams/{practiceExam}/simulate', [PracticeExamController::class, 'simulate'])
            ->middleware('throttle:quiz-mutate')->name('practice-exams.simulate');

        // Challenge a friend (تحدّي الأصدقاء)
        Route::prefix('challenges')->name('challenges.')->group(function () {
            Route::post('/', [ChallengeController::class, 'store'])->middleware('throttle:quiz-mutate')->name('store');
            Route::post('/join', [ChallengeController::class, 'join'])->middleware('throttle:quiz-mutate')->name('join');
            Route::get('/{code}', [ChallengeController::class, 'show'])->middleware('throttle:quiz-read')->name('show');
        });

        // Leaderboard (المتصدرين)
        Route::get('/leaderboard', [LeaderboardController::class, 'index'])
            ->middleware('throttle:quiz-read')->name('leaderboard.index');

        // Quizzes (الاختبارات الذاتية) — sandboxed; static paths before {quizSession}
        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::middleware('throttle:quiz-read')->group(function () {
                Route::get('/pool-count', [QuizController::class, 'poolCount'])->name('pool-count');
                Route::get('/active', [QuizController::class, 'active'])->name('active');
                Route::get('/', [QuizController::class, 'index'])->name('index');
            });
            Route::post('/', [QuizController::class, 'store'])
                ->middleware('throttle:quiz-mutate')->name('store');
            Route::get('/{quizSession}', [QuizController::class, 'show'])
                ->middleware('throttle:quiz-read')->name('show');
            Route::post('/{quizSession}/answer', [QuizController::class, 'answer'])
                ->middleware('throttle:quiz-answer')->name('answer');
            Route::post('/{quizSession}/complete', [QuizController::class, 'complete'])
                ->middleware('throttle:quiz-mutate')->name('complete');
            Route::post('/{quizSession}/abandon', [QuizController::class, 'abandon'])
                ->middleware('throttle:quiz-mutate')->name('abandon');
        });
    });
});
