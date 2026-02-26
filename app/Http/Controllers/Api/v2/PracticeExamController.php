<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\PracticeExamIndexRequest;
use App\Http\Resources\v2\PracticeExamResource;
use App\Models\PracticeExam;
use App\Services\PracticeExamService;
use Illuminate\Http\JsonResponse;

class PracticeExamController extends BaseApiController
{
    protected PracticeExamService $practiceExamService;

    public function __construct(PracticeExamService $practiceExamService)
    {
        $this->practiceExamService = $practiceExamService;
    }

    /**
     * Get Practice Exams (نماذج الامتحانات والاختبارات التجريبية)
     * 
     * يجلب قائمة بنماذج الامتحانات التدريبية المتوفرة للطلاب ليختبروا بها أنفسهم.
     * يمكن التصفح بدون تسجيل دخول.
     * يدعم الفلترة، وبشكل افتراضي لا يقوم بتقسيم النتائج إلى صفحات (Pagination) إلا إذا طُلب ذلك.
     *
     * @queryParam search string optional نص للبحث عن نموذج معين عن طريق عنوانه. Example: Physics
     * @queryParam paginate boolean optional مرر `true` للحصول على استجابة مقسمة بصفحات. Example: true
     * @queryParam per_page integer optional عدد العناصر في الصفحة الواحدة في حال تفعيل `paginate`. Example: 10
     *
     * @group Browsing / Practice Exams (الاختبارات التجريبية)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{practice_exams: array, pagination?: array}}
     * @response 500 array{status: int, message: string}
     */
    public function index(PracticeExamIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = $filters['paginate'] ?? false; // Don't paginate by default for practice exams

            $practiceExams = $this->practiceExamService->getAllPracticeExams($filters);

            if ($practiceExams instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                return $this->successResponse([
                    'practice_exams' => PracticeExamResource::collection($practiceExams->items()),
                    'pagination' => [
                        'current_page' => $practiceExams->currentPage(),
                        'per_page' => $practiceExams->perPage(),
                        'total' => $practiceExams->total(),
                        'last_page' => $practiceExams->lastPage(),
                    ]
                ], 'Practice exams retrieved successfully');
            }

            return $this->successResponse([
                'practice_exams' => PracticeExamResource::collection($practiceExams)
            ], 'Practice exams retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve practice exams: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Practice Exam Details (تفاصيل النموذج التجريبي وأسئلته)
     * 
     * يجلب تفاصيل نموذج الاختبار التدريبي مع كافة الأسئلة والخيارات المرتبطة به.
     * يجب استخدام هذه النهاية (Endpoint) عندما يبدأ الطالب جلسة اختبار للبدء في حل الأسئلة.
     *
     * @pathParam practice_exam integer required المعرف الافتراضي لنموذج الاختبار المُراد جلبه. Example: 3
     *
     * @group Browsing / Practice Exams (الاختبارات التجريبية)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{practice_exam: array}}
     * @response 404 array{status: int, message: string}
     */
    public function show(PracticeExam $practiceExam): JsonResponse
    {
        try {
            $practiceExamWithDetails = $this->practiceExamService->getPracticeExamDetails($practiceExam);

            return $this->successResponse([
                'practice_exam' => new PracticeExamResource($practiceExamWithDetails)
            ], 'Practice exam details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve practice exam details: ' . $e->getMessage(), 500);
        }
    }
}
