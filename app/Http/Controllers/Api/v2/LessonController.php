<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\LessonResource;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الدروس (Lessons)
 *
 * Student-facing lesson viewing + progress. Lessons are gated to the
 * student's own exam; opening one marks it in-progress, and completion
 * records time spent.
 */
class LessonController extends BaseApiController
{
    /**
     * Lesson Detail (تفاصيل الدرس)
     *
     * يعيد محتوى الدرس بصفحاته ويحدّث حالته إلى "قيد التنفيذ". الدرس متاح
     * فقط ضمن اختبار الطالب.
     *
     * @group Lessons (الدروس)
     */
    public function show(Lesson $lesson): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Lessons are only available for students', Response::HTTP_FORBIDDEN);
        }

        // Gate: only lessons belonging to the student's exam, and active.
        if ($lesson->exam_id !== $student->exam_id || !$lesson->is_active) {
            return $this->errorResponse('Lesson not found', Response::HTTP_NOT_FOUND);
        }

        $progress = StudentLessonProgress::firstOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lesson->id],
            ['scheduled_date' => now()->toDateString(), 'status' => 'pending']
        );

        // Lock future-day lessons: a lesson can't be opened before its
        // scheduled day arrives. Already-started/completed lessons stay open.
        if (
            $progress->status === 'pending'
            && $progress->scheduled_date
            && $progress->scheduled_date->isFuture()
            && !$progress->scheduled_date->isToday()
        ) {
            return $this->errorResponse(
                'هذا الدرس مجدول ليوم ' . $progress->scheduled_date->translatedFormat('l j F')
                    . '. سيكون متاحاً في موعده.',
                Response::HTTP_FORBIDDEN
            );
        }

        // Flip pending → in_progress on first open (now that it's unlocked).
        if ($progress->status === 'pending') {
            $progress->markAsStarted();
        }

        $lesson->load([
            'pages' => fn ($q) => $q->orderBy('page_number'),
        ])->loadCount('pages');
        $lesson->setRelation('studentProgress', collect([$progress]));

        return $this->successResponse([
            'lesson' => new LessonResource($lesson),
        ], 'Lesson retrieved successfully');
    }

    /**
     * Complete Lesson (إتمام الدرس)
     *
     * يحدّد الدرس كمكتمل ويسجّل الوقت المستغرق (بالدقائق).
     *
     * @bodyParam time_spent_minutes integer optional الوقت المستغرق بالدقائق.
     *
     * @group Lessons (الدروس)
     */
    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Lessons are only available for students', Response::HTTP_FORBIDDEN);
        }

        if ($lesson->exam_id !== $student->exam_id) {
            return $this->errorResponse('Lesson not found', Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'time_spent_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        $progress = StudentLessonProgress::firstOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lesson->id],
            ['scheduled_date' => now()->toDateString(), 'status' => 'pending']
        );

        // Idempotent: re-completing keeps the original completion time, but
        // still accepts an updated time-spent figure.
        if ($progress->status !== 'completed') {
            $progress->markAsCompleted();
        }
        if (array_key_exists('time_spent_minutes', $validated) && $validated['time_spent_minutes'] !== null) {
            $progress->time_spent_minutes = ($progress->time_spent_minutes ?? 0) + $validated['time_spent_minutes'];
            $progress->save();
        }

        return $this->successResponse([
            'status' => $progress->status,
            'completed_at' => optional($progress->completed_at)->toIso8601String(),
            'time_spent_minutes' => $progress->time_spent_minutes,
        ], 'Lesson marked as completed');
    }
}
