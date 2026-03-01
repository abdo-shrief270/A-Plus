<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Support\Collection;

class ExamService
{
    /**
     * Get all exams with optional filters
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllExams(array $filters = []): Collection
    {
        $query = Exam::query();

        if (isset($filters['active'])) {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->get();
    }

    /**
     * Get exam details with unified structure
     *
     * @param Exam $exam
     * @return array
     */
    public function getExamDetails(Exam $exam): array
    {
        $details = [
            'id' => $exam->id,
            'name' => $exam->name,
            'active' => $exam->active,
            'type' => 'section-based',
            'structure' => []
        ];

        if ($exam->sections()->exists()) {
            $details['structure']['sections'] = $exam->sections()
                ->with([
                    'categories' => function ($query) {
                        $query->withCount('questions');
                    }
                ])
                ->get()
                ->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'categories' => $section->categories->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'name' => $category->name,
                                'description' => $category->description,
                                'questions_count' => $category->questions_count
                            ];
                        })
                    ];
                });
        }

        return $details;
    }

    /**
     * Get all sections for an exam
     *
     * @param Exam $exam
     * @return Collection
     */
    public function getExamSections(Exam $exam): Collection
    {
        return $exam->sections()
            ->with([
                'categories' => function ($query) {
                    $query->withCount('questions');
                }
            ])
            ->get();
    }
}
