<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\QuestionIndexRequest;
use App\Http\Requests\v2\QuestionSearchRequest;
use App\Http\Resources\v2\QuestionDetailResource;
use App\Http\Resources\v2\QuestionResource;
use App\Models\Question;
use App\Models\SectionCategory;
use App\Services\AiExplanationService;
use App\Services\QuestionService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends BaseApiController
{
    protected QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * AI Explanation (شرح بالذكاء الاصطناعي)
     *
     * يولّد شرحاً مبسّطاً للسؤال بالذكاء الاصطناعي (يُخزَّن ويُعاد استخدامه).
     * يُخصم رصيد مرة واحدة لكل سؤال؛ المشتركون بدون خصم. غير متاح إن لم
     * يُضبط مفتاح OpenAI.
     *
     * @group Gamification / Answer Cycle (دورة الإجابة والتلعيب)
     */
    public function aiExplanation(Question $question, AiExplanationService $ai, WalletService $wallet): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('متاح للطلاب فقط', Response::HTTP_FORBIDDEN);
        }

        // Already cached → serve free (no charge, no OpenAI call).
        if (filled($question->ai_explanation)) {
            return $this->successResponse([
                'explanation' => $question->ai_explanation,
                'cached' => true,
                'balance' => $wallet->getBalance($student),
            ], 'AI explanation retrieved');
        }

        if (!$ai->enabled()) {
            return $this->errorResponse(
                'ميزة الشرح بالذكاء الاصطناعي غير متاحة حالياً.',
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        // Charge once per question (subscribers free), like the answer flow.
        $cost = (int) config('ai.explanation_cost', 0);
        if ($cost > 0 && !$student->hasUnlimitedAccess()) {
            $paid = $wallet->payForContent($student, $question, $cost, 'ai_explanation');
            if (!$paid) {
                return $this->errorResponse(
                    'رصيدك غير كافٍ للحصول على شرح بالذكاء الاصطناعي.',
                    Response::HTTP_PAYMENT_REQUIRED
                );
            }
        }

        $explanation = $ai->explain($question);
        if (!$explanation) {
            return $this->errorResponse('تعذّر توليد الشرح حالياً. حاول لاحقاً.', Response::HTTP_BAD_GATEWAY);
        }

        return $this->successResponse([
            'explanation' => $explanation,
            'cached' => false,
            'balance' => $wallet->getBalance($student),
        ], 'AI explanation generated');
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
     * Get Correct Answer (الإجابة الصحيحة)
     *
     * يجلب الإجابة الصحيحة لسؤال معين. مفيد لعرضها داخل نافذة الشرح بدون
     * الحاجة لإعادة جلب تفاصيل السؤال كاملاً.
     *
     * @pathParam question integer required المعرف الافتراضي للسؤال. Example: 1
     *
     * @group Browsing / Questions (الأسئلة)
     *
     * @response 200 array{status: int, message: string, data: array{id: int, text: string}}
     * @response 404 array{status: int, message: string}
     */
    public function correctAnswer(Question $question): JsonResponse
    {
        $answer = $question->answers()->where('is_correct', true)->first(['id', 'text']);

        if (!$answer) {
            return $this->errorResponse('Correct answer not found', 404);
        }

        return $this->successResponse(
            ['id' => $answer->id, 'text' => $answer->text],
            'Correct answer retrieved successfully'
        );
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

            $mapQuestion = fn($q) => ['id' => $q->id, 'text' => $q->text];

            if ($questions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                return $this->successResponse([
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                    ],
                    'questions' => array_map($mapQuestion, $questions->items()),
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
                'questions' => $questions->map($mapQuestion)->values(),
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
