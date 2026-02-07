<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentStatsService
{
    /**
     * Get student statistics for charts over a period.
     *
     * @param string $period week|month|3months|year
     * @param User|null $user For scoping by school/parent
     * @return array
     */
    public function getStudentStats(string $period, ?User $user = null): array
    {
        $dateRange = $this->getDateRange($period);

        $query = Student::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        // Scope by user type
        if ($user) {
            if ($user->type === 'parent') {
                $studentIds = $user->studentParent()->pluck('student_id')->toArray();
                $query->whereIn('id', $studentIds);
            }
        }

        // Get all students in period and group in PHP (database-agnostic)
        $students = $query->clone()->get(['id', 'created_at']);

        // Group by period format
        $newStudents = $students->groupBy(function ($student) use ($period) {
            return match ($period) {
                'week', 'month' => $student->created_at->format('Y-m-d'),
                '3months' => $student->created_at->format('Y-W'),
                'year' => $student->created_at->format('Y-m'),
                default => $student->created_at->format('Y-m-d'),
            };
        })->map(fn($g) => $g->count())->toArray();

        // Get active students (those with any lesson progress in period)
        $activeStudentsQuery = Student::query()
            ->whereHas('lessonProgress', function ($q) use ($dateRange) {
                $q->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']]);
            });

        $activeStudentsData = $activeStudentsQuery->get(['id', 'created_at']);

        $activeStudents = $activeStudentsData->groupBy(function ($student) use ($period) {
            return match ($period) {
                'week', 'month' => $student->created_at->format('Y-m-d'),
                '3months' => $student->created_at->format('Y-W'),
                'year' => $student->created_at->format('Y-m'),
                default => $student->created_at->format('Y-m-d'),
            };
        })->map(fn($g) => $g->count())->toArray();

        // Generate labels for the period
        $labels = $this->generateLabels($period, $dateRange);

        return [
            'period' => $period,
            'labels' => $labels,
            'new_students' => array_map(fn($label) => $newStudents[$label] ?? 0, $labels),
            'active_students' => array_map(fn($label) => $activeStudents[$label] ?? 0, $labels),
            'total_new' => array_sum(array_map(fn($label) => $newStudents[$label] ?? 0, $labels)),
            'total_active' => array_sum(array_map(fn($label) => $activeStudents[$label] ?? 0, $labels)),
        ];
    }

    private function getDateRange(string $period): array
    {
        $end = now();

        return match ($period) {
            'week' => ['start' => $end->copy()->subWeek(), 'end' => $end],
            'month' => ['start' => $end->copy()->subMonth(), 'end' => $end],
            '3months' => ['start' => $end->copy()->subMonths(3), 'end' => $end],
            'year' => ['start' => $end->copy()->subYear(), 'end' => $end],
            default => ['start' => $end->copy()->subMonth(), 'end' => $end],
        };
    }

    private function getGroupFormat(string $period): string
    {
        return match ($period) {
            'week' => '%Y-%m-%d',
            'month' => '%Y-%m-%d',
            '3months' => '%Y-%W',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    private function generateLabels(string $period, array $dateRange): array
    {
        $labels = [];
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);

        while ($current->lte($end)) {
            $labels[] = match ($period) {
                'week', 'month' => $current->format('Y-m-d'),
                '3months' => $current->format('Y-W'),
                'year' => $current->format('Y-m'),
                default => $current->format('Y-m-d'),
            };

            $current = match ($period) {
                'week', 'month' => $current->addDay(),
                '3months' => $current->addWeek(),
                'year' => $current->addMonth(),
                default => $current->addDay(),
            };
        }

        return $labels;
    }
}
