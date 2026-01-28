<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\StudyPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateStudyPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Student $student
    ) {
    }

    public function handle(StudyPlanService $studyPlanService): void
    {
        try {
            $result = $studyPlanService->generateStudyPlan($this->student);

            if ($result['success']) {
                Log::info('Study plan generated successfully', [
                    'student_id' => $this->student->id,
                    'total_lessons' => $result['total_lessons'],
                    'days_until_exam' => $result['days_until_exam'],
                ]);

                // TODO: Send notification to student
                // $this->student->user->notify(new StudyPlanGeneratedNotification($result));
            } else {
                Log::warning('Failed to generate study plan', [
                    'student_id' => $this->student->id,
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error generating study plan', [
                'student_id' => $this->student->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
