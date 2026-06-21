<?php

namespace App\Http\Controllers\Api\v2;

use App\Exceptions\QuizConflictException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\QuizAnswerRequest;
use App\Http\Requests\v2\QuizPoolCountRequest;
use App\Http\Requests\v2\QuizStoreRequest;
use App\Http\Resources\v2\QuizSessionDetailResource;
use App\Http\Resources\v2\QuizSessionResource;
use App\Models\QuizSession;
use App\Models\Student;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الاختبارات الذاتية (Quizzes)
 *
 * Self-service student quizzes. Fully sandboxed: answers recorded here never
 * touch StudentAnswer / ScoreService / revision metrics.
 */
class QuizController extends BaseApiController
{
    public function __construct(protected QuizService $quizService)
    {
    }

    /**
     * Quiz Pool Count (عدد الأسئلة المتاحة)
     *
     * يعيد عدد الأسئلة المتاحة للاختيارات الحالية (النطاق، المصدر، الصعوبة)
     * ليعرضه منشئ الاختبار للطالب قبل البدء.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function poolCount(QuizPoolCountRequest $request): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Quizzes are only available for students', Response::HTTP_FORBIDDEN);
        }

        $available = $this->quizService->poolCount($student, $request->validated());

        return $this->successResponse(['available' => $available], 'Pool count retrieved successfully');
    }

    /**
     * Active Quiz Session (الاختبار قيد التنفيذ)
     *
     * يعيد جلسة الاختبار الجارية للطالب إن وُجدت (لاستئنافها)، أو null.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function active(): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Quizzes are only available for students', Response::HTTP_FORBIDDEN);
        }

        $session = QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();

        if ($session) {
            $this->quizService->syncExpiry($session);
            if (!$session->isInProgress()) {
                $session = null;
            }
        }

        return $this->successResponse([
            'session' => $session ? new QuizSessionResource($session) : null,
        ], 'Active session retrieved successfully');
    }

    /**
     * Quiz History (سجل الاختبارات)
     *
     * يعيد قائمة اختبارات الطالب السابقة والجارية مع ترقيم صفحات.
     *
     * @queryParam per_page integer optional Default 10
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function index(Request $request): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Quizzes are only available for students', Response::HTTP_FORBIDDEN);
        }

        $perPage = min(50, max(1, (int) $request->input('per_page', 10)));
        $sessions = QuizSession::where('student_id', $student->id)
            ->withCount(['questions as answered_count' => fn ($q) => $q->whereNotNull('answered_at')])
            ->latest('started_at')
            ->paginate($perPage);

        return $this->successResponse([
            'sessions' => QuizSessionResource::collection($sessions->items()),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
        ], 'Quiz sessions retrieved successfully');
    }

    /**
     * Start Quiz (بدء اختبار جديد)
     *
     * ينشئ جلسة اختبار جديدة بالإعدادات المحددة ويجمّد أسئلتها عشوائياً.
     * يُرفض الطلب إذا كان لدى الطالب اختبار قيد التنفيذ (409 مع رقم الجلسة).
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function store(QuizStoreRequest $request): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Quizzes are only available for students', Response::HTTP_FORBIDDEN);
        }

        try {
            $session = $this->quizService->createSession($student, $request->validated());
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => $e->payload,
            ], Response::HTTP_CONFLICT);
        }

        $session->load(['questions.question.answers', 'questions.question.type', 'challenge', 'practiceExam']);

        return $this->successResponse([
            'session' => new QuizSessionDetailResource($session),
        ], 'Quiz session created successfully', Response::HTTP_CREATED);
    }

    /**
     * Quiz Session Details (تفاصيل الاختبار)
     *
     * يعيد الجلسة كاملة بأسئلتها. أثناء وضع الاختبار لا تُكشف الإجابات
     * الصحيحة أو الشرح إلا بعد الإنهاء.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function show(QuizSession $quizSession): JsonResponse
    {
        $student = $this->student();
        if (!$student || $quizSession->student_id !== $student->id) {
            return $this->errorResponse('Quiz session not found', Response::HTTP_NOT_FOUND);
        }

        $this->quizService->syncExpiry($quizSession);
        $quizSession->load(['questions.question.answers', 'questions.question.type', 'challenge', 'practiceExam']);

        return $this->successResponse([
            'session' => new QuizSessionDetailResource($quizSession),
        ], 'Quiz session retrieved successfully');
    }

    /**
     * Submit Quiz Answer (إرسال إجابة)
     *
     * يسجّل إجابة سؤال داخل الجلسة. في وضع التوجيه تُعاد الإجابة الصحيحة
     * والشرح فوراً؛ في وضع الاختبار يُعاد إقرار فقط.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function answer(QuizAnswerRequest $request, QuizSession $quizSession): JsonResponse
    {
        $student = $this->student();
        if (!$student || $quizSession->student_id !== $student->id) {
            return $this->errorResponse('Quiz session not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->quizService->answerQuestion(
                $quizSession,
                (int) $request->validated('question_id'),
                (int) $request->validated('answer_id')
            );
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => array_merge($e->payload, [
                    'session' => new QuizSessionResource($quizSession->fresh()),
                ]),
            ], Response::HTTP_CONFLICT);
        }

        return $this->successResponse($result, 'Answer recorded successfully');
    }

    /**
     * Finish Quiz (إنهاء الاختبار)
     *
     * ينهي الجلسة ويحتسب النتيجة. آمن التكرار (idempotent) — استدعاؤه على
     * جلسة منتهية يعيد نتيجتها كما هي.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function complete(QuizSession $quizSession): JsonResponse
    {
        $student = $this->student();
        if (!$student || $quizSession->student_id !== $student->id) {
            return $this->errorResponse('Quiz session not found', Response::HTTP_NOT_FOUND);
        }

        $session = $this->quizService->completeSession($quizSession);
        $session->load(['questions.question.answers', 'questions.question.type', 'challenge', 'practiceExam']);

        return $this->successResponse([
            'session' => new QuizSessionDetailResource($session),
            // League points granted for finishing today's daily challenge (null otherwise)
            'daily_bonus_points' => $session->getAttribute('daily_bonus_awarded'),
            // Updated league total so the navbar counter stays live after a bonus.
            'total_score' => (int) $student->refresh()->current_score,
        ], 'Quiz session completed successfully');
    }

    /**
     * Abandon Quiz (إلغاء الاختبار)
     *
     * يلغي الجلسة الجارية بدون احتساب نتيجة.
     *
     * @group Quizzes (الاختبارات الذاتية)
     */
    public function abandon(QuizSession $quizSession): JsonResponse
    {
        $student = $this->student();
        if (!$student || $quizSession->student_id !== $student->id) {
            return $this->errorResponse('Quiz session not found', Response::HTTP_NOT_FOUND);
        }

        $session = $this->quizService->abandonSession($quizSession);

        return $this->successResponse([
            'session' => new QuizSessionResource($session),
        ], 'Quiz session abandoned successfully');
    }

    private function student(): ?Student
    {
        return auth('api')->user()?->student;
    }
}
