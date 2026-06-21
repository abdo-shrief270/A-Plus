<?php

namespace App\Http\Controllers\Api\v2;

use App\Exceptions\QuizConflictException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\QuizStoreRequest;
use App\Http\Resources\v2\QuizSessionDetailResource;
use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags تحدّي الأصدقاء (Challenges)
 */
class ChallengeController extends BaseApiController
{
    public function __construct(protected ChallengeService $service)
    {
    }

    /**
     * Create Challenge (إنشاء تحدٍ)
     *
     * يجمّد مجموعة أسئلة عشوائية برمز دعوة، ويبدأ جلسة المُنشئ. شارك الرمز
     * مع صديقك لينضمّ ويحلّ نفس الأسئلة.
     *
     * @group Challenges (تحدّي الأصدقاء)
     */
    public function store(QuizStoreRequest $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Challenges are only available for students', Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->service->create($student, $request->validated());
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => $e->payload,
            ], Response::HTTP_CONFLICT);
        }

        $result['session']->load(['questions.question.answers', 'questions.question.type']);

        return $this->successResponse([
            'invite_code' => $result['challenge']->invite_code,
            'session' => new QuizSessionDetailResource($result['session']),
        ], 'Challenge created successfully', Response::HTTP_CREATED);
    }

    /**
     * Join Challenge (الانضمام لتحدٍ)
     *
     * ينضمّ لتحدٍ عبر رمز الدعوة ويبدأ جلسة على نفس مجموعة الأسئلة.
     *
     * @group Challenges (تحدّي الأصدقاء)
     */
    public function join(Request $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Challenges are only available for students', Response::HTTP_FORBIDDEN);
        }

        $code = strtoupper(trim((string) $request->input('code')));

        try {
            $result = $this->service->join($student, $code);
        } catch (QuizConflictException $e) {
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
                'data' => $e->payload,
            ], Response::HTTP_CONFLICT);
        }

        $result['session']->load(['questions.question.answers', 'questions.question.type']);

        return $this->successResponse([
            'invite_code' => $result['challenge']->invite_code,
            'session' => new QuizSessionDetailResource($result['session']),
        ], 'Joined challenge successfully');
    }

    /**
     * Challenge Results (نتائج التحدي)
     *
     * يعيد ترتيب المشاركين ونتائجهم.
     *
     * @group Challenges (تحدّي الأصدقاء)
     */
    public function show(string $code): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Challenges are only available for students', Response::HTTP_FORBIDDEN);
        }

        $challenge = Challenge::where('invite_code', strtoupper($code))->first();
        if (!$challenge) {
            return $this->errorResponse('Challenge not found', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse($this->service->results($challenge), 'Challenge results retrieved successfully');
    }
}
