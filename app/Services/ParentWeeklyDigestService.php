<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\StudentLessonProgress;
use App\Models\StudentScore;
use App\Models\User;
use Carbon\Carbon;

/**
 * Computes a parent-facing weekly summary of each child's study activity.
 * Used by the scheduled digest notification and the on-demand parent endpoint.
 */
class ParentWeeklyDigestService
{
    /** One child's activity since a given date. */
    public function childSummary(Student $student, Carbon $since): array
    {
        $answered = StudentAnswer::where('student_id', $student->id)
            ->where('created_at', '>=', $since)->count();
        $correct = StudentAnswer::where('student_id', $student->id)
            ->where('created_at', '>=', $since)->where('is_correct', true)->count();

        $quizzes = QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_COMPLETED)
            ->where('completed_at', '>=', $since)->count();

        $lessons = StudentLessonProgress::where('student_id', $student->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)->count();

        $points = (int) StudentScore::where('student_id', $student->id)
            ->where('created_at', '>=', $since)->sum('score');

        return [
            'student_id' => $student->id,
            'name' => $student->user?->name,
            'answered' => $answered,
            'correct' => $correct,
            'accuracy' => $answered > 0 ? round($correct / $answered * 100, 1) : 0.0,
            'quizzes_completed' => $quizzes,
            'lessons_completed' => $lessons,
            'points_earned' => $points,
            'was_active' => $answered > 0 || $quizzes > 0 || $lessons > 0,
        ];
    }

    /**
     * All summaries for a parent's children since $since.
     *
     * @return array<int, array>
     */
    public function summariesForParent(User $parent, Carbon $since): array
    {
        return $parent->studentParent()
            ->with('student.user')
            ->get()
            ->map(fn ($link) => $link->student)
            ->filter()
            ->map(fn (Student $student) => $this->childSummary($student, $since))
            ->values()
            ->all();
    }

    /** A short Arabic sentence summarizing one child's week (for notifications). */
    public function childHeadline(array $summary): string
    {
        if (!$summary['was_active']) {
            return "{$summary['name']}: لا نشاط هذا الأسبوع — شجّعه على المتابعة.";
        }

        return "{$summary['name']}: حلّ {$summary['answered']} سؤالاً بدقّة {$summary['accuracy']}%"
            . "، أكمل {$summary['lessons_completed']} درساً و{$summary['quizzes_completed']} اختباراً"
            . "، وكسب {$summary['points_earned']} نقطة.";
    }
}
