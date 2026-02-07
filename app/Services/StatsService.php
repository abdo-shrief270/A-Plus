<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentLessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * Get platform-wide statistics for schools/parents dashboard.
     *
     * @param User|null $user The authenticated user (for scoping)
     * @return array
     */
    public function getPlatformStats(?User $user = null): array
    {
        $studentQuery = Student::query();
        $enrollmentQuery = Enrollment::query();

        // Scope by user type
        if ($user) {
            if ($user->type === 'school') {
                $schoolId = $user->studentSchool?->school_id;
                if ($schoolId) {
                    $studentQuery->whereHas('studentSchool', fn($q) => $q->where('school_id', $schoolId));
                }
            } elseif ($user->type === 'parent') {
                $studentIds = $user->studentParent()->pluck('student_id')->toArray();
                $studentQuery->whereIn('id', $studentIds);
            }
        }

        // Total students
        $totalStudents = $studentQuery->count();

        // Total courses
        $totalCourses = Course::active()->count();

        // Average progress of all students
        $avgProgress = StudentLessonProgress::avg('progress_percentage') ?? 0;

        // New students in last month
        $newStudentsLastMonth = Student::where('created_at', '>=', now()->subMonth())->count();

        // Active enrollments
        $activeEnrollments = $enrollmentQuery->active()->count();

        // New enrollments this week
        $newEnrollmentsThisWeek = Enrollment::where('created_at', '>=', now()->subWeek())->count();

        // Total completed lessons
        $completedLessons = StudentLessonProgress::where('status', 'completed')->count();

        return [
            'total_students' => $totalStudents,
            'total_courses' => $totalCourses,
            'average_progress' => round($avgProgress, 2),
            'new_students_last_month' => $newStudentsLastMonth,
            'active_enrollments' => $activeEnrollments,
            'new_enrollments_this_week' => $newEnrollmentsThisWeek,
            'completed_lessons' => $completedLessons,
        ];
    }
}
