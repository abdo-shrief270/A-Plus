<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\QuestionIndexRequest;
use App\Http\Requests\v2\QuestionSearchRequest;
use App\Http\Resources\v2\QuestionDetailResource;
use App\Http\Resources\v2\QuestionResource;
use App\Models\Question;
use App\Models\ExamSubject;
use App\Models\SectionCategory;
use App\Services\QuestionService;
use Illuminate\Http\JsonResponse;

class QuestionController extends BaseApiController
{
    protected QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Get trending (new) questions
     * GET /api/v2/questions/trending
     *
     * @param QuestionIndexRequest $request
     * @return JsonResponse
     */
    public function trending(QuestionIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = true; // Always paginate trending questions

            $questions = $this->questionService->getTrendingQuestions($filters);

            return $this->successResponse([
                'questions' => QuestionDetailResource::collection($questions->items()),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'per_page' => $questions->perPage(),
                    'total' => $questions->total(),
                    'last_page' => $questions->lastPage(),
                ]
            ], 'Trending questions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve trending questions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get question details by ID
     * GET /api/v2/questions/{question}
     *
     * @param Question $question
     * @return JsonResponse
     */
    public function show(Question $question): JsonResponse
    {
        try {
            $questionWithDetails = $this->questionService->getQuestionDetails($question);

            return $this->successResponse([
                'question' => new QuestionDetailResource($questionWithDetails)
            ], 'Question details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve question details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get questions by subject
     * GET /api/v2/subjects/{subject}/questions
     *
     * @param ExamSubject $subject
     * @param QuestionIndexRequest $request
     * @return JsonResponse
     */
    public function bySubject(ExamSubject $subject, QuestionIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = $filters['paginate'] ?? true;

            $questions = $this->questionService->getQuestionsBySubject($subject, $filters);

            if ($questions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                return $this->successResponse([
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'description' => $subject->description,
                    ],
                    'questions' => QuestionDetailResource::collection($questions->items()),
                    'pagination' => [
                        'current_page' => $questions->currentPage(),
                        'per_page' => $questions->perPage(),
                        'total' => $questions->total(),
                        'last_page' => $questions->lastPage(),
                    ]
                ], 'Subject questions retrieved successfully');
            }

            return $this->successResponse([
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'description' => $subject->description,
                ],
                'questions' => QuestionDetailResource::collection($questions)
            ], 'Subject questions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve subject questions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get questions by category
     * GET /api/v2/categories/{category}/questions
     *
     * @param SectionCategory $category
     * @param QuestionIndexRequest $request
     * @return JsonResponse
     */
    public function byCategory(SectionCategory $category, QuestionIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = $filters['paginate'] ?? true;

            $questions = $this->questionService->getQuestionsByCategory($category, $filters);

            if ($questions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                return $this->successResponse([
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                    ],
                    'questions' => QuestionDetailResource::collection($questions->items()),
                    'pagination' => [
                        'current_page' => $questions->currentPage(),
                        'per_page' => $questions->perPage(),
                        'total' => $questions->total(),
                        'last_page' => $questions->lastPage(),
                    ]
                ], 'Category questions retrieved successfully');
            }

            return $this->successResponse([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                ],
                'questions' => QuestionDetailResource::collection($questions)
            ], 'Category questions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve category questions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search questions
     * GET /api/v2/questions/search
     *
     * @param QuestionSearchRequest $request
     * @return JsonResponse
     */
    public function search(QuestionSearchRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $searchTerm = $filters['q'];
            unset($filters['q']);
            $filters['paginate'] = true;

            $questions = $this->questionService->searchQuestions($searchTerm, $filters);

            return $this->successResponse([
                'search_query' => $searchTerm,
                'questions' => QuestionDetailResource::collection($questions->items()),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'per_page' => $questions->perPage(),
                    'total' => $questions->total(),
                    'last_page' => $questions->lastPage(),
                ]
            ], 'Search results retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search questions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recent questions
     * GET /api/v2/questions/recent
     *
     * @param QuestionIndexRequest $request
     * @return JsonResponse
     */
    public function recent(QuestionIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = true;

            $questions = $this->questionService->getRecentQuestions($filters);

            return $this->successResponse([
                'questions' => QuestionDetailResource::collection($questions->items()),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'per_page' => $questions->perPage(),
                    'total' => $questions->total(),
                    'last_page' => $questions->lastPage(),
                ]
            ], 'Recent questions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve recent questions: ' . $e->getMessage(), 500);
        }
    }
}
