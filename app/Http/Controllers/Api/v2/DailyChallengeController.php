<?php

namespace App\Http\Controllers\Api\v2;

use App\Exceptions\QuizConflictException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\QuizSessionDetailResource;
use App\Http\Resources\v2\QuizSessionResource;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags التحدي اليومي (Daily Challenge)
 */
class DailyChallengeController extends BaseApiController
{
    public function __construct(protected QuizService $quizService)
    {
    }

    /**
     * Daily Challenge Status (حالة التحدي اليومي)
     *
     * يعيد جلسة تحدي اليوم إن وُجدت، وسلسلة الأيام المتتالية (Streak)،
     * وقيمة مكافأة الإكمال.
     *
     * @group Daily Challenge (التحدي اليومي)
     */
    public function show(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Daily challenge is only available for students', Response::HTTP_FORBIDDEN);
        }

        $today = $this->quizService->todaysChallenge($student);
        if ($today) {
            $this->quizService->syncExpiry($today);
        }

        return $this->successResponse([
            'today' => $today ? new QuizSessionResource($today) : null,
            'streak' => $this->quizService->dailyStreak($student),
            'bonus' => [
                'base' => QuizService::DAILY_BONUS_BASE,
                'excellent' => QuizService::DAILY_BONUS_EXCELLENT,
                'excellent_threshold' => 80,
            ],
            'config' => [
                'question_count' => QuizService::DAILY_QUESTION_COUNT,
                'time_limit_minutes' => QuizService::DAILY_TIME_LIMIT_MINUTES,
            ],
        ], 'Daily challenge status retrieved successfully');
    }

    /**
     * Start Daily Challenge (بدء تحدي اليوم)
     *
     * ينشئ جلسة تحدي اليوم (10 أسئلة عشوائية من كامل اختبار الطالب خلال
     * 10 دقائق) أو يعيد الجلسة الحالية إن سبق بدؤها.
     *
     * @group Daily Challenge (التحدي اليومي)
     */
    public function start(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Daily challenge is only available for students', Response::HTTP_FORBIDDEN);
        }

        try {
            $session = $this->quizService->startDailyChallenge($student);
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => $e->payload,
            ], Response::HTTP_CONFLICT);
        } catch (ValidationException $e) {
            throw $e;
        }

        $session->load(['questions.question.answers', 'questions.question.type']);

        return $this->successResponse([
            'session' => new QuizSessionDetailResource($session),
            'streak' => $this->quizService->dailyStreak($student),
        ], 'Daily challenge started successfully');
    }
}
