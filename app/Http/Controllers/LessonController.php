<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $user = auth('api')->user() ?: auth('schools')->user();

            if (!$user) {
                return $this->apiResponse(401, 'Unauthenticated');
            }

            $exam = $user->student?->exam;

            if (!$exam) {
                return $this->apiResponse(404, 'No exam found for this student');
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

            return $this->apiResponse(200, 'Lessons Returned Successfully', null, [
                'lessons' => $lessons
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Lessons Returning failed: ' . $e->getMessage());
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

            return $this->apiResponse(200, 'Lesson Data Returned Successfully', null, [
                'lesson' => $lesson
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Lesson Data Returning failed: ' . $e->getMessage());
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
                return $this->apiResponse(404, 'Student not found');
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

            return $this->apiResponse(200, 'Progress Updated Successfully', null, [
                'progress' => $progress
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Progress Update failed: ' . $e->getMessage());
        }
    }
}
