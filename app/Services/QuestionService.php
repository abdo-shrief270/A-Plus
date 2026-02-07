<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ExamSubject;
use App\Models\SectionCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionService
{
    /**
     * Get trending (new) questions with answers
     *
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getTrendingQuestions(array $filters = []): LengthAwarePaginator|Collection
    {
        $query = Question::where('is_new', true)
            ->with([
                'answers' => function ($query) {
                    $query->orderBy('order');
                }
            ]);

        // Filter by difficulty
        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        // Filter by exam if provided
        if (isset($filters['exam_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('subjects', function ($subQuery) use ($filters) {
                    $subQuery->where('exam_id', $filters['exam_id']);
                })->orWhereHas('categories.section', function ($catQuery) use ($filters) {
                    $catQuery->where('exam_id', $filters['exam_id']);
                });
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get question details with answers and relationships
     *
     * @param Question $question
     * @return Question
     */
    public function getQuestionDetails(Question $question): Question
    {
        return $question->load([
            'answers' => function ($query) {
                $query->orderBy('order');
            },
            'subjects.exam',
            'categories.section.exam',
            'type'
        ]);
    }

    /**
     * Get questions by subject
     *
     * @param ExamSubject $subject
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getQuestionsBySubject(ExamSubject $subject, array $filters = []): LengthAwarePaginator|Collection
    {
        $query = $subject->questions()
            ->with([
                'answers' => function ($query) {
                    $query->orderBy('order');
                }
            ]);

        // Filter by difficulty
        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        // Filter by is_new
        if (isset($filters['is_new'])) {
            $query->where('is_new', filter_var($filters['is_new'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get questions by category
     *
     * @param SectionCategory $category
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getQuestionsByCategory(SectionCategory $category, array $filters = []): LengthAwarePaginator|Collection
    {
        $query = $category->questions()
            ->with([
                'answers' => function ($query) {
                    $query->orderBy('order');
                }
            ]);

        // Filter by difficulty
        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        // Filter by is_new
        if (isset($filters['is_new'])) {
            $query->where('is_new', filter_var($filters['is_new'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Search questions by text
     *
     * @param string $searchTerm
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function searchQuestions(string $searchTerm, array $filters = []): LengthAwarePaginator|Collection
    {
        $query = Question::where('text', 'like', '%' . $searchTerm . '%')
            ->with([
                'answers' => function ($query) {
                    $query->orderBy('order');
                }
            ]);

        // Filter by difficulty
        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        // Filter by is_new
        if (isset($filters['is_new'])) {
            $query->where('is_new', filter_var($filters['is_new'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get recently added questions (is_new = true)
     *
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getRecentQuestions(array $filters = []): LengthAwarePaginator|Collection
    {
        $query = Question::where('is_new', true)
            ->with([
                'answers' => function ($query) {
                    $query->orderBy('order');
                }
            ])
            ->orderBy('created_at', 'desc');

        // Filter by difficulty
        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Get question's parent context (subject or category)
     *
     * @param Question $question
     * @return array|null
     */
    public function getQuestionContext(Question $question): ?array
    {
        // Check if question belongs to a subject
        $subject = $question->subjects()->with('exam')->first();
        if ($subject) {
            return [
                'type' => 'subject',
                'id' => $subject->id,
                'name' => $subject->name,
                'description' => $subject->description,
                'exam' => [
                    'id' => $subject->exam->id,
                    'name' => $subject->exam->name
                ]
            ];
        }

        // Check if question belongs to a category
        $category = $question->categories()->with('section.exam')->first();
        if ($category) {
            return [
                'type' => 'category',
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'section' => [
                    'id' => $category->section->id,
                    'name' => $category->section->name
                ],
                'exam' => [
                    'id' => $category->section->exam->id,
                    'name' => $category->section->exam->name
                ]
            ];
        }

        return null;
    }
}
