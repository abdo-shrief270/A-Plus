<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\QuestionIndexRequest;
use App\Http\Resources\v2\ArticleResource;
use App\Http\Resources\v2\QuestionDetailResource;
use App\Models\Article;
use App\Models\SectionCategory;
use Illuminate\Http\JsonResponse;

class ArticleController extends BaseApiController
{
    /**
     * Get Articles by Category (قطع الفئة)
     *
     * يجلب قائمة بالقطع التابعة لفئة معينة (Section Category).
     *
     * @pathParam category integer required المعرف الافتراضي للفئة. Example: 5
     *
     * @group Browsing / Articles (القطع)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{category: array, articles: array}}
     */
    public function byCategory(SectionCategory $category): JsonResponse
    {
        try {
            $articles = $category->articles()
                ->where('is_active', true)
                ->withCount('questions')
                ->get();

            return $this->successResponse([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                ],
                'articles' => ArticleResource::collection($articles),
            ], 'Category articles retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve category articles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Article Details (تفاصيل القطعة)
     *
     * يجلب تفاصيل قطعة معينة مع أسئلتها.
     *
     * @pathParam article integer required المعرف الافتراضي للقطعة. Example: 1
     *
     * @group Browsing / Articles (القطع)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{article: array, questions: array}}
     */
    public function show(Article $article): JsonResponse
    {
        try {
            $article->load(['category.section.exam', 'questions' => function ($query) {
                $query->with(['answers' => function ($q) {
                    $q->orderBy('order');
                }, 'type']);
            }]);
            $article->loadCount('questions');

            return $this->successResponse([
                'article' => new ArticleResource($article),
                'questions' => QuestionDetailResource::collection($article->questions),
            ], 'Article details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve article details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Questions by Article (أسئلة القطعة)
     *
     * يجلب قائمة بالأسئلة التابعة لقطعة معينة مع ترقيم صفحات.
     *
     * @pathParam article integer required المعرف الافتراضي للقطعة. Example: 1
     * @queryParam per_page integer optional عدد العناصر في الصفحة. Default: 15
     *
     * @group Browsing / Articles (القطع)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{article: array, questions: array, pagination: array}}
     */
    public function questions(Article $article, QuestionIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $perPage = $filters['per_page'] ?? 15;

            $questions = $article->questions()
                ->with(['answers' => function ($q) {
                    $q->orderBy('order');
                }, 'type'])
                ->paginate($perPage);

            return $this->successResponse([
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                ],
                'questions' => QuestionDetailResource::collection($questions->items()),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'per_page' => $questions->perPage(),
                    'total' => $questions->total(),
                    'last_page' => $questions->lastPage(),
                ],
            ], 'Article questions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve article questions: ' . $e->getMessage(), 500);
        }
    }
}
