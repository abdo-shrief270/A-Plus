<?php

namespace App\Services;

use App\Models\Question;
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
            $query->whereHas('categories.section', function ($catQuery) use ($filters) {
                $catQuery->where('exam_id', $filters['exam_id']);
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
            'categories.section.exam',
            'type'
        ]);
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
     * Get question's parent context (category)
     *
     * @param Question $question
     * @return array|null
     */
    public function getQuestionContext(Question $question): ?array
    {
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
