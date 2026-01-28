<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentLessonProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudyPlanService
{
    /**
     * Generate study plan for a student based on their exam date
     *
     * @param Student $student
     * @return array
     */
    public function generateStudyPlan(Student $student): array
    {
        // Get all active lessons for the student's exam
        $lessons = Lesson::where('exam_id', $student->exam_id)
            ->active()
            ->ordered()
            ->get();

        if ($lessons->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No lessons found for this exam',
            ];
        }

        // Calculate days until exam
        $examDate = Carbon::parse($student->exam_date);
        $today = Carbon::today();
        $daysUntilExam = $today->diffInDays($examDate, false);

        if ($daysUntilExam < 0) {
            return [
                'success' => false,
                'message' => 'Exam date has already passed',
            ];
        }

        if ($daysUntilExam == 0) {
            $daysUntilExam = 1; // At least schedule for today
        }

        // Delete existing progress for this student (if regenerating)
        StudentLessonProgress::where('student_id', $student->id)->delete();

        // Distribute lessons across available days
        $distribution = $this->distributeLessons($lessons, $daysUntilExam);

        // Create progress records
        $progressRecords = [];
        $currentDate = $today->copy();

        foreach ($distribution as $dayIndex => $dayLessons) {
            foreach ($dayLessons as $lesson) {
                $progressRecords[] = [
                    'student_id' => $student->id,
                    'lesson_id' => $lesson->id,
                    'scheduled_date' => $currentDate->toDateString(),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $currentDate->addDay();
        }

        DB::table('student_lesson_progress')->insert($progressRecords);

        return [
            'success' => true,
            'total_lessons' => $lessons->count(),
            'days_until_exam' => $daysUntilExam,
            'lessons_per_day' => ceil($lessons->count() / $daysUntilExam),
            'distribution' => $distribution->map(fn($day) => $day->count())->toArray(),
        ];
    }

    /**
     * Distribute lessons evenly across available days
     *
     * @param \Illuminate\Support\Collection $lessons
     * @param int $days
     * @return \Illuminate\Support\Collection
     */
    protected function distributeLessons($lessons, int $days)
    {
        $totalLessons = $lessons->count();
        $lessonsPerDay = ceil($totalLessons / $days);

        $distribution = collect();
        $lessonIndex = 0;

        for ($day = 0; $day < $days; $day++) {
            $dayLessons = collect();
            $lessonsForThisDay = min($lessonsPerDay, $totalLessons - $lessonIndex);

            for ($i = 0; $i < $lessonsForThisDay; $i++) {
                if ($lessonIndex < $totalLessons) {
                    $dayLessons->push($lessons[$lessonIndex]);
                    $lessonIndex++;
                }
            }

            $distribution->push($dayLessons);
        }

        return $distribution;
    }

    /**
     * Get student's study plan summary
     *
     * @param Student $student
     * @return array
     */
    public function getStudyPlanSummary(Student $student): array
    {
        $progress = StudentLessonProgress::where('student_id', $student->id)
            ->with('lesson')
            ->get();

        $total = $progress->count();
        $completed = $progress->where('status', 'completed')->count();
        $inProgress = $progress->where('status', 'in_progress')->count();
        $pending = $progress->where('status', 'pending')->count();

        return [
            'total_lessons' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'days_until_exam' => Carbon::parse($student->exam_date)->diffInDays(Carbon::today(), false),
        ];
    }
}
