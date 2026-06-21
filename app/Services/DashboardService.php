<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentSchool;
use App\Models\Subscription;
use App\Models\User;

class DashboardService
{
    /**
     * Bundle dashboard data scoped by the user's role.
     */
    public function bundle(?User $user): array
    {
        $studentIds = $this->scopedStudentIds($user);
        $userIds = $this->scopedUserIdsFromStudentIds($studentIds);

        $totalStudents = count($studentIds);
        $totalCourses = Course::active()->count();

        $activeEnrollments = empty($userIds) ? 0 : Enrollment::active()
            ->whereIn('user_id', $userIds)
            ->count();

        $activeSubscriptions = empty($studentIds) ? 0 : Subscription::active()
            ->whereIn('student_id', $studentIds)
            ->count();

        $newStudentsLastMonth = empty($studentIds) ? 0 : Student::whereIn('id', $studentIds)
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        $newEnrollmentsThisWeek = empty($userIds) ? 0 : Enrollment::whereIn('user_id', $userIds)
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // Recent students (latest 5)
        $recentStudents = empty($studentIds) ? collect() : Student::query()
            ->whereIn('id', $studentIds)
            ->with(['user', 'league', 'exam', 'wallet'])
            ->withExists(['subscriptions as has_unlimited_points' => function ($q) {
                $q->where('status', 'active')
                    ->where(function ($qq) {
                        $qq->whereNull('ends_at')->orWhere('ends_at', '>', now());
                    })
                    ->whereHas('plan', fn ($pq) => $pq->where('type', 'subscription'));
            }])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Recent enrollments (latest 5)
        $recentEnrollments = empty($userIds) ? collect() : Enrollment::query()
            ->whereIn('user_id', $userIds)
            ->with(['course', 'user.student'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Recent subscriptions (latest 5)
        $recentSubscriptions = empty($studentIds) ? collect() : Subscription::query()
            ->whereIn('student_id', $studentIds)
            ->with(['plan', 'student.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Trending courses (top 5)
        $trendingCourses = Course::query()
            ->active()
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'total_students' => $totalStudents,
                'total_courses' => $totalCourses,
                'active_enrollments' => $activeEnrollments,
                'active_subscriptions' => $activeSubscriptions,
                'new_students_last_month' => $newStudentsLastMonth,
                'new_enrollments_this_week' => $newEnrollmentsThisWeek,
            ],
            'recent_students' => $recentStudents,
            'recent_enrollments' => $recentEnrollments,
            'recent_subscriptions' => $recentSubscriptions,
            'trending_courses' => $trendingCourses,
        ];
    }

    private function scopedStudentIds(?User $user): array
    {
        if (!$user) return [];

        if ($user->type === 'parent') {
            return $user->studentParent()->pluck('student_id')->toArray();
        }

        if ($user->type === 'school') {
            $schoolId = $user->school?->id;
            if (!$schoolId) return [];
            return StudentSchool::where('school_id', $schoolId)
                ->pluck('student_id')
                ->toArray();
        }

        if ($user->type === 'student') {
            return $user->student?->id ? [$user->student->id] : [];
        }

        return [];
    }

    private function scopedUserIdsFromStudentIds(array $studentIds): array
    {
        if (empty($studentIds)) return [];
        return Student::whereIn('id', $studentIds)->pluck('user_id')->toArray();
    }
}
