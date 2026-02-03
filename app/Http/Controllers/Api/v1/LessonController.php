<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
// use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends BaseApiController
{
    // use ApiResponse;

    public function index()
    {
        try {
            $user = auth('api')->user() ?: auth('schools')->user();

            if (!$user) {
                return $this->errorResponse('Unauthenticated', 401);
            }

            $exam = $user->student?->exam;

            if (!$exam) {
                return $this->errorResponse('No exam found for this student', 404);
            }

            $lessons = $exam->lessons()
                ->active()
                ->withCount(['pages'])
                ->get();

            // Load progress
            $progress = StudentLessonProgress::where('student_id', $user->student->id)
                ->get()
                ->keyBy('lesson_id');

            $lessons->each(function ($lesson) use ($progress) {
                $lesson->status = $progress->get($lesson->id)?->status ?? 'locked';
                $lesson->progress_percentage = $progress->get($lesson->id)?->progress_percentage ?? 0;
            });

            return $this->successResponse([
                'lessons' => $lessons
            ], 'Lessons Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Lessons Returning failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(Lesson $lesson)
    {
        try {
            $lesson->load([
                'pages' => function ($query) {
                    $query->ordered();
                }
            ]);

            return $this->successResponse([
                'lesson' => $lesson
            ], 'Lesson Data Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Lesson Data Returning failed: ' . $e->getMessage(), 500);
        }
    }

    public function updateProgress(Request $request, Lesson $lesson)
    {
        $request->validate([
            'status' => 'required|in:in_progress,completed',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'last_page_id' => 'nullable|exists:lesson_pages,id',
        ]);

        try {
            $user = auth('api')->user() ?: auth('schools')->user();
            $student = $user?->student;

            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            $progress = StudentLessonProgress::updateOrCreate(
                ['student_id' => $student->id, 'lesson_id' => $lesson->id],
                [
                    'status' => $request->status,
                    'scheduled_date' => $request->scheduled_date ?? now()->toDateString(),
                    'progress_percentage' => $request->progress_percentage ?? ($request->status === 'completed' ? 100 : 0),
                    'last_page_id' => $request->last_page_id,
                    'completed_at' => $request->status === 'completed' ? now() : null,
                ]
            );

            return $this->successResponse([
                'progress' => $progress
            ], 'Progress Updated Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Progress Update failed: ' . $e->getMessage(), 500);
        }
    }
}
