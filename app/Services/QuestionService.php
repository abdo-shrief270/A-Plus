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

        $result = isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();

        $this->attachSiblingIds($result instanceof LengthAwarePaginator ? $result->items() : $result);

        return $result;
    }

    /**
     * Get question details with answers and relationships
     *
     * @param Question $question
     * @return Question
     */
    public function getQuestionDetails(Question $question): Question
    {
        $question->load([
            'answers' => function ($query) {
                $query->orderBy('order');
            },
            'categories.section.exam',
            'type'
        ]);

        $this->attachSiblingIds(collect([$question]));

        return $question;
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
        // Get question IDs from direct category pivot AND from articles under this category
        $directQuestionIds = $category->questions()->pluck('questions.id');
        $articleQuestionIds = Question::whereHas('articles', function ($q) use ($category) {
            $q->where('section_category_id', $category->id);
        })->pluck('id');

//        $allQuestionIds = $directQuestionIds->merge($articleQuestionIds)->unique();

        $query = Question::whereIn('id', $directQuestionIds)
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

        $result = isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();

        $this->attachSiblingIds($result instanceof LengthAwarePaginator ? $result->items() : $result);

        return $result;
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

        $result = isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();

        $this->attachSiblingIds($result instanceof LengthAwarePaginator ? $result->items() : $result);

        return $result;
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

        $result = isset($filters['paginate']) && $filters['paginate']
            ? $query->paginate($perPage)
            : $query->get();

        $this->attachSiblingIds($result instanceof LengthAwarePaginator ? $result->items() : $result);

        return $result;
    }

    /**
     * Attach previous_question_id and next_question_id to each question
     * based on sibling questions in the same category.
     *
     * @param \Illuminate\Support\Collection|array $questions
     * @return \Illuminate\Support\Collection|array
     */
    public function attachSiblingIds($questions)
    {
        $questionIds = collect($questions)->pluck('id')->all();

        if (empty($questionIds)) {
            return $questions;
        }

        // Get the first category for each question in one query
        $pivots = \DB::table('category_questions')
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('question_id');

        // Collect unique category IDs (first category per question)
        $categoryMap = [];
        foreach ($questionIds as $qId) {
            if (isset($pivots[$qId]) && $pivots[$qId]->isNotEmpty()) {
                $categoryMap[$qId] = $pivots[$qId]->first()->section_category_id;
            }
        }

        $categoryIds = array_unique(array_values($categoryMap));

        // Get all questions per category, ordered by id
        $categoryQuestions = \DB::table('category_questions')
            ->whereIn('section_category_id', $categoryIds)
            ->orderBy('question_id')
            ->get()
            ->groupBy('section_category_id');

        // Build ordered lists per category
        $orderedByCategory = [];
        foreach ($categoryQuestions as $catId => $rows) {
            $orderedByCategory[$catId] = $rows->pluck('question_id')->values()->all();
        }

        // Assign prev/next to each question
        foreach ($questions as $question) {
            $catId = $categoryMap[$question->id] ?? null;
            if ($catId && isset($orderedByCategory[$catId])) {
                $siblings = $orderedByCategory[$catId];
                $index = array_search($question->id, $siblings);

                $question->previous_question_id = ($index !== false && $index > 0) ? $siblings[$index - 1] : null;
                $question->next_question_id = ($index !== false && $index < count($siblings) - 1) ? $siblings[$index + 1] : null;
            } else {
                $question->previous_question_id = null;
                $question->next_question_id = null;
            }
        }

        return $questions;
    }

    /**
     * Get question's parent context (article -> category -> section -> exam)
     *
     * @param Question $question
     * @return array|null
     */
    public function getQuestionContext(Question $question): ?array
    {
        // Check if question belongs to an article
        $article = $question->articles()->with('category.section.exam')->first();
        if ($article && $article->category) {
            $category = $article->category;
            return [
                'type' => 'article',
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                ],
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                ],
                'section' => [
                    'id' => $category->section->id,
                    'name' => $category->section->name,
                ],
                'exam' => [
                    'id' => $category->section->exam->id,
                    'name' => $category->section->exam->name,
                ],
            ];
        }

        // Check if question belongs to a category directly
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

