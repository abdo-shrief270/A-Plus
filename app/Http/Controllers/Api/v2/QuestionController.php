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
     * Get Trending Questions (الأسئلة الشائعة والأكثر حلاً)
     * 
     * يجلب قائمة مقسمة بصفحات بالأسئلة التي يكثر حلها حالياً على المنصة.
     * مفيد لعرض "الأسئلة الرائجة" في واجهة الطالب الرئيسية (Student Dashboard).
     *
     * @queryParam per_page integer optional عدد العناصر في الصفحة الواحدة. Default: 15
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{questions: array, pagination?: array}}
     * @response 500 array{status: int, message: string}
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
     * Get Question Details (تفاصيل السؤال)
     * 
     * يجلب كافة تفاصيل سؤال معين بناءً على الـ ID الخاص به، يتضمن الإجابات المحتملة (Answers) بدون توضيح أيها الصحيح (لأغراض أمنية)، 
     * بالإضافة لنص السؤال، مستوى الصعوبة، والفيديو التوضيحي للحل (إن وُجد).
     *
     * @pathParam question integer required المعرف الافتراضي للسؤال المُراد جلبه. Example: 150
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{question: array}}
     * @response 404 array{status: int, message: string}
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
     * Get Questions by Subject (أسئلة مادة معينة)
     * 
     * يجلب الأسئلة المُصفاة بناءً على مادة دراسية تابعة لامتحان/مرحلة (Exam Subject).
     * يدعم تقسيم الصفحات (Pagination) بشكل افتراضي.
     *
     * @pathParam subject integer required المعرف الافتراضي للمادة (Exam Subject). Example: 10
     * @queryParam per_page integer optional عدد العناصر.
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{subject: array, questions: array, pagination?: array}}
     * @response 404 array{status: int, message: string}
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
     * Get Questions by Category (أسئلة تصنيف فرعي)
     * 
     * يجلب قائمة بالأسئلة التابعة لقسم/تصنيف فرعي معين (Section Category). 
     * مثل الأسئلة المتعلقة بـ "قسم الجبر" المتفرع من مستوى أعلى.
     *
     * @pathParam category integer required المعرف الافتراضي للقسم الفرعي. Example: 5
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{category: array, questions: array, pagination?: array}}
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
     * Search Questions (البحث في بنك الأسئلة)
     * 
     * يُمكن هذا المسار الطالب من البحث النصي الشامل عن الأسئلة عبر النظام باستخدام معامل البحث `q`.
     * يتم إرجاع النتائج المقتربة، مع ترقيم صفحات (Pagination).
     *
     * @queryParam q string required نص البحث المُراد البحث عنه في نصوص الأسئلة. Example: التفاضل
     * @queryParam per_page integer optional
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{search_query: string, questions: array, pagination: array}}
     * @response 422 array{status: int, message: string} - إذا لم يتم تمرير حقل `q`
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
     * Get Recent Questions (أحدث الأسئلة المُضافة)
     * 
     * يجلب الأسئلة فرزاً من الأحدث إلى الأقدم بناءً على تاريخ إنشائها في قاعدة البيانات.
     *
     * @queryParam per_page integer optional
     *
     * @group Browsing / Questions (الأسئلة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{questions: array, pagination: array}}
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
