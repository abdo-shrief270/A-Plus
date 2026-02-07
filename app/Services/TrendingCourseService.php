<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;

class TrendingCourseService
{
    /**
     * Get trending courses ordered by enrollment count.
     *
     * @param int $limit
     * @return Collection
     */
    public function getTrendingCourses(int $limit = 10): Collection
    {
        return Course::query()
            ->active()
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit($limit)
            ->get();
    }
}
