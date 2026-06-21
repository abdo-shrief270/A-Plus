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

        // Preserve any existing progress (status/timestamps) so regenerating
        // the schedule — e.g. after the exam date changes — never wipes out
        // lessons the student already started or completed. Only the
        // scheduled_date is recomputed.
        $existing = StudentLessonProgress::where('student_id', $student->id)
            ->get()
            ->keyBy('lesson_id');

        StudentLessonProgress::where('student_id', $student->id)->delete();

        // Distribute lessons across available days
        $distribution = $this->distributeLessons($lessons, $daysUntilExam);

        // Create progress records
        $progressRecords = [];
        $currentDate = $today->copy();

        foreach ($distribution as $dayIndex => $dayLessons) {
            foreach ($dayLessons as $lesson) {
                $prev = $existing->get($lesson->id);
                $progressRecords[] = [
                    'student_id' => $student->id,
                    'lesson_id' => $lesson->id,
                    'scheduled_date' => $currentDate->toDateString(),
                    'status' => $prev->status ?? 'pending',
                    'started_at' => $prev->started_at ?? null,
                    'completed_at' => $prev->completed_at ?? null,
                    'time_spent_minutes' => $prev->time_spent_minutes ?? 0,
                    'created_at' => $prev->created_at ?? now(),
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
     * Lesson-reminder counts for a student: lessons due today (scheduled today,
     * still pending) and overdue (scheduled before today, never completed).
     *
     * @return array{due_today: int, overdue: int}
     */
    public function reminderCounts(Student $student): array
    {
        $today = Carbon::today();

        $dueToday = StudentLessonProgress::where('student_id', $student->id)
            ->where('status', 'pending')
            ->whereDate('scheduled_date', $today)
            ->count();

        $overdue = StudentLessonProgress::where('student_id', $student->id)
            ->where('status', '!=', 'completed')
            ->whereDate('scheduled_date', '<', $today)
            ->count();

        return ['due_today' => $dueToday, 'overdue' => $overdue];
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

        $today = Carbon::today();
        // Due now = scheduled today or earlier and not yet completed.
        $dueToday = $progress
            ->where('status', '!=', 'completed')
            ->filter(fn ($p) => $p->scheduled_date && $p->scheduled_date->lte($today))
            ->count();
        // Overdue = scheduled strictly before today and never completed.
        $overdue = $progress
            ->where('status', '!=', 'completed')
            ->filter(fn ($p) => $p->scheduled_date && $p->scheduled_date->lt($today))
            ->count();

        return [
            'total_lessons' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            // diffInDays(future, false) from today is positive; reversed args
            // (the old bug) produced negatives.
            'days_until_exam' => $student->exam_date
                ? (int) Carbon::today()->diffInDays(Carbon::parse($student->exam_date), false)
                : null,
        ];
    }

    /**
     * Ensure the student has a study plan, generating one lazily on first
     * access. Returns false if generation isn't possible (no lessons / no
     * exam date / exam passed).
     */
    public function ensurePlan(Student $student): bool
    {
        if (!$student->exam_id || !$student->exam_date) {
            return false;
        }

        $progressCount = StudentLessonProgress::where('student_id', $student->id)->count();
        $activeLessonCount = Lesson::where('exam_id', $student->exam_id)->active()->count();

        // Regenerate when the plan doesn't cover every active lesson. This also
        // self-heals the case where opening a lesson directly (deep link)
        // created a single ad-hoc progress row before the plan was ever built.
        // Regeneration preserves existing status/timestamps, so nothing is lost.
        if ($progressCount > 0 && $progressCount >= $activeLessonCount) {
            return true;
        }

        if ($activeLessonCount === 0) {
            return false;
        }

        return (bool) ($this->generateStudyPlan($student)['success'] ?? false);
    }

    /**
     * The student's plan as lessons grouped by scheduled date, each with its
     * lesson metadata and progress status. Oldest day first.
     *
     * @return array<int, array{date: string, is_today: bool, is_past: bool, lessons: array}>
     */
    public function getGroupedPlan(Student $student): array
    {
        $progress = StudentLessonProgress::where('student_id', $student->id)
            ->with('lesson:id,exam_id,title,description,logo,color,order,duration_minutes')
            ->get()
            ->filter(fn ($p) => $p->lesson !== null);

        $today = Carbon::today();

        // Build the day buckets first (oldest → newest).
        $days = $progress
            ->groupBy(fn ($p) => optional($p->scheduled_date)->toDateString() ?? 'unscheduled')
            ->sortKeys()
            ->map(function ($dayProgress, $date) use ($today) {
                $dateObj = $date !== 'unscheduled' ? Carbon::parse($date) : null;
                // A day is locked if it's scheduled for a future calendar day.
                $isFuture = $dateObj ? $dateObj->gt($today) : false;
                $lessons = $dayProgress
                    ->sortBy(fn ($p) => $p->lesson->order)
                    ->map(fn ($p) => [
                        'progress_id' => $p->id,
                        'lesson_id' => $p->lesson_id,
                        'title' => $p->lesson->title,
                        'description' => $p->lesson->description,
                        'logo' => $p->lesson->logo,
                        'color' => $p->lesson->color,
                        'duration_minutes' => $p->lesson->duration_minutes,
                        'status' => $p->status,
                        // Future-day lessons not yet started are locked until
                        // their day arrives; started/completed stay accessible.
                        'is_locked' => $isFuture && $p->status === 'pending',
                        'started_at' => optional($p->started_at)->toIso8601String(),
                        'completed_at' => optional($p->completed_at)->toIso8601String(),
                    ])->values()->all();

                return [
                    'date' => $date,
                    'date_obj' => $dateObj,
                    'is_today' => $dateObj?->isSameDay($today) ?? false,
                    'is_past' => $dateObj ? $dateObj->lt($today) : false,
                    'is_locked' => $isFuture,
                    'completed_count' => collect($lessons)->where('status', 'completed')->count(),
                    'lessons' => $lessons,
                ];
            })->values();

        // Anchor week numbering to the first scheduled day; each week is a
        // sequential 7-day block from that anchor (الأسبوع 1, 2, ...).
        $anchor = $days->firstWhere('date_obj', '!=', null)['date_obj'] ?? $today;

        return $days
            ->groupBy(function ($day) use ($anchor) {
                if (!$day['date_obj']) {
                    return 9999; // unscheduled bucket sorts last
                }

                return (int) floor($anchor->diffInDays($day['date_obj'], false) / 7) + 1;
            })
            ->sortKeys()
            ->map(function ($weekDays, $weekNumber) use ($today) {
                $dated = $weekDays->filter(fn ($d) => $d['date_obj'] !== null);
                $start = $dated->first()['date_obj'] ?? null;
                $end = $dated->last()['date_obj'] ?? null;
                $totalLessons = $weekDays->sum(fn ($d) => count($d['lessons']));
                $completed = $weekDays->sum(fn ($d) => $d['completed_count']);

                return [
                    'week_number' => $weekNumber === 9999 ? null : (int) $weekNumber,
                    'label' => $weekNumber === 9999 ? 'غير مجدول' : 'الأسبوع ' . $weekNumber,
                    'start_date' => $start?->toDateString(),
                    'end_date' => $end?->toDateString(),
                    'is_current' => $start && $end
                        ? $today->betweenIncluded($start, $end)
                        : false,
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completed,
                    'days' => $weekDays->map(fn ($d) => [
                        'date' => $d['date'],
                        'is_today' => $d['is_today'],
                        'is_past' => $d['is_past'],
                        'is_locked' => $d['is_locked'],
                        'lessons' => $d['lessons'],
                    ])->values()->all(),
                ];
            })->values()->all();
    }
}
