<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags المراجعة الذكية (Smart Review)
 *
 * Per-student setting for how long a wrong answer rests before it's due for
 * review again. Stored as hours; the client may set it in hours or days.
 */
class ReviewSettingsController extends BaseApiController
{
    private const DEFAULT_HOURS = 24;
    private const MIN_HOURS = 1;
    private const MAX_HOURS = 8760; // 365 days

    public function show(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Review is only available for students', Response::HTTP_FORBIDDEN);
        }

        return $this->successResponse($this->payload($student), 'Review settings retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Review is only available for students', Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'cooldown_value' => ['required', 'integer', 'min:1'],
            'cooldown_unit' => ['required', 'in:hours,days'],
        ]);

        $hours = $data['cooldown_unit'] === 'days'
            ? (int) $data['cooldown_value'] * 24
            : (int) $data['cooldown_value'];
        $hours = max(self::MIN_HOURS, min(self::MAX_HOURS, $hours));

        $student->update(['review_cooldown_hours' => $hours]);

        return $this->successResponse($this->payload($student->fresh()), 'تم تحديث إعدادات المراجعة');
    }

    private function payload(Student $student): array
    {
        $hours = (int) ($student->review_cooldown_hours ?: self::DEFAULT_HOURS);
        $isWholeDays = $hours % 24 === 0;

        return [
            'cooldown_hours' => $hours,
            'cooldown_days' => intdiv($hours, 24),
            'cooldown_unit' => $isWholeDays ? 'days' : 'hours',
            'cooldown_value' => $isWholeDays ? intdiv($hours, 24) : $hours,
            'default_cooldown_hours' => self::DEFAULT_HOURS,
            'min_hours' => self::MIN_HOURS,
            'max_hours' => self::MAX_HOURS,
        ];
    }
}
