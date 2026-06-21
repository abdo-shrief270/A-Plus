<?php

namespace App\Http\Controllers\Api\v2;

use App\Exceptions\QuizConflictException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\QuizSessionDetailResource;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags محاكاة الاختبار (Exam Simulation)
 *
 * A full mock exam: one exam-mode session spanning every section of the
 * student's exam, proportionally distributed. Reuses the quiz engine, so all
 * the same answer/complete/review endpoints apply.
 */
class ExamSimulationController extends BaseApiController
{
    public function __construct(protected QuizService $quizService)
    {
    }

    /**
     * Simulation Info (معلومات المحاكاة)
     *
     * يعيد إعدادات المحاكاة (عدد الأسئلة، الزمن) للعرض قبل البدء.
     *
     * @group Exam Simulation (محاكاة الاختبار)
     */
    public function show(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Exam simulation is only available for students', Response::HTTP_FORBIDDEN);
        }

        return $this->successResponse([
            'config' => [
                'question_count' => QuizService::SIMULATION_QUESTION_COUNT,
                'seconds_per_question' => QuizService::SIMULATION_SECONDS_PER_QUESTION,
            ],
        ], 'Exam simulation info retrieved successfully');
    }

    /**
     * Start Simulation (بدء محاكاة الاختبار)
     *
     * ينشئ جلسة محاكاة تغطي جميع أقسام اختبار الطالب بتوزيع متناسب.
     * يُرفض الطلب إذا كان لدى الطالب اختبار قيد التنفيذ (409).
     *
     * @group Exam Simulation (محاكاة الاختبار)
     */
    public function start(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Exam simulation is only available for students', Response::HTTP_FORBIDDEN);
        }

        try {
            $session = $this->quizService->startSimulation($student);
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => $e->payload,
            ], Response::HTTP_CONFLICT);
        }

        $session->load(['questions.question.answers', 'questions.question.type']);

        return $this->successResponse([
            'session' => new QuizSessionDetailResource($session),
        ], 'Exam simulation started successfully');
    }
}
