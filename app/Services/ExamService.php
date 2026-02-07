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
        $type = $this->getExamType($exam);

        $details = [
            'id' => $exam->id,
            'name' => $exam->name,
            'active' => $exam->active,
            'type' => $type,
            'structure' => []
        ];

        if ($type === 'subject-based') {
            $details['structure']['subjects'] = $exam->subjects()
                ->withCount('questions')
                ->get()
                ->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'description' => $subject->description,
                        'questions_count' => $subject->questions_count
                    ];
                });
        } elseif ($type === 'section-based') {
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
        } elseif ($type === 'mixed') {
            // Both subjects and sections
            $details['structure']['subjects'] = $exam->subjects()
                ->withCount('questions')
                ->get()
                ->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'description' => $subject->description,
                        'questions_count' => $subject->questions_count
                    ];
                });

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
     * Determine exam type based on its structure
     *
     * @param Exam $exam
     * @return string
     */
    public function getExamType(Exam $exam): string
    {
        $hasSubjects = $exam->subjects()->exists();
        $hasSections = $exam->sections()->exists();

        if ($hasSubjects && $hasSections) {
            return 'mixed';
        } elseif ($hasSubjects) {
            return 'subject-based';
        } elseif ($hasSections) {
            return 'section-based';
        }

        return 'unknown';
    }

    /**
     * Get all subjects for an exam
     *
     * @param Exam $exam
     * @return Collection
     */
    public function getExamSubjects(Exam $exam): Collection
    {
        return $exam->subjects()
            ->withCount('questions')
            ->get();
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
