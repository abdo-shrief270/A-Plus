<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\QuestionDetailResource;
use App\Models\Question;
use App\Models\StudentAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags المراجعة الذكية (Smart Review)
 *
 * Spaced review over the student's wrong answers. A wrong question becomes
 * "due" after resting for COOLDOWN_HOURS; answering it again goes through the
 * regular POST /v2/questions/answer endpoint — a correct answer flips the
 * StudentAnswer row and removes the question from this queue automatically,
 * a wrong answer refreshes its timestamp and re-queues it for tomorrow.
 */
class ReviewController extends BaseApiController
{
    /** Hours a wrong answer rests before it is due for review. */
    private const COOLDOWN_HOURS = 24;

    /**
     * Review Queue (قائمة مراجعة الأخطاء)
     *
     * يعيد الأسئلة التي أخطأ بها الطالب وأصبحت مستحقة للمراجعة
     * (مرّ على آخر محاولة 24 ساعة)، مرتبة من الأقدم، مع ملخص العدّادات.
     *
     * @queryParam per_page integer optional Default 10
     *
     * @group Smart Review (المراجعة الذكية)
     */
    public function queue(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user?->student) {
            return $this->errorResponse('Review is only available for students', Response::HTTP_FORBIDDEN);
        }

        $cooldownHours = (int) ($user->student->review_cooldown_hours ?: self::COOLDOWN_HOURS);
        $threshold = now()->subHours($cooldownHours);

        $wrong = StudentAnswer::where('user_id', $user->id)->where('is_correct', false);

        $dueCount = (clone $wrong)->where('updated_at', '<=', $threshold)->count();
        $restingCount = (clone $wrong)->where('updated_at', '>', $threshold)->count();
        $nextResting = (clone $wrong)->where('updated_at', '>', $threshold)->min('updated_at');

        $perPage = min(50, max(1, (int) $request->input('per_page', 10)));
        $dueAnswers = (clone $wrong)
            ->where('updated_at', '<=', $threshold)
            ->orderBy('updated_at')
            ->paginate($perPage);

        // Preserve oldest-first order while loading full question payloads.
        $questionIds = collect($dueAnswers->items())->pluck('question_id');
        $questions = Question::whereIn('id', $questionIds)
            ->with([
                'answers' => fn ($q) => $q->orderBy('order'),
                'type',
                'categories.section.exam',
                'articles.category.section.exam',
            ])
            ->get()
            ->sortBy(fn ($q) => $questionIds->search($q->id))
            ->values();

        return $this->successResponse([
            'questions' => QuestionDetailResource::collection($questions),
            'summary' => [
                'due_count' => $dueCount,
                'resting_count' => $restingCount,
                'next_due_at' => $nextResting
                    ? \Carbon\Carbon::parse($nextResting)->addHours($cooldownHours)->toIso8601String()
                    : null,
                'cooldown_hours' => $cooldownHours,
                'cooldown_days' => intdiv($cooldownHours, 24),
                'cooldown_is_whole_days' => $cooldownHours % 24 === 0,
            ],
            'pagination' => [
                'current_page' => $dueAnswers->currentPage(),
                'per_page' => $dueAnswers->perPage(),
                'total' => $dueAnswers->total(),
                'last_page' => $dueAnswers->lastPage(),
            ],
        ], 'Review queue retrieved successfully');
    }
}
