<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Course::query()
            ->active()
            ->with(['exams'])
            ->withCount('enrollments')
            ->orderByDesc('created_at');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['exam_id'])) {
            $query->whereHas('exams', fn ($q) => $q->where('exams.id', $filters['exam_id']));
        }

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($filters['per_page'] ?? 12);
    }

    public function show(int $id): ?Course
    {
        return Course::with(['exams'])->withCount('enrollments')->find($id);
    }
}
