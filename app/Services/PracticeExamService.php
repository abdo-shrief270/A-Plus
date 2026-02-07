<?php

namespace App\Services;

use App\Models\PracticeExam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PracticeExamService
{
    /**
     * Get all practice exams with optional filters
     *
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getAllPracticeExams(array $filters = []): LengthAwarePaginator|Collection
    {
        $query = PracticeExam::with([
            'questions.answers' => function ($query) {
                $query->orderBy('order');
            }
        ]);

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get practice exam details with questions
     *
     * @param PracticeExam $practiceExam
     * @return PracticeExam
     */
    public function getPracticeExamDetails(PracticeExam $practiceExam): PracticeExam
    {
        return $practiceExam->load([
            'questions.answers' => function ($query) {
                $query->orderBy('order');
            }
        ]);
    }
}
