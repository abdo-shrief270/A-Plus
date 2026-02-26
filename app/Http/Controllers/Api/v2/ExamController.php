<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\ExamIndexRequest;
use App\Http\Resources\v2\ExamDetailResource;
use App\Http\Resources\v2\ExamResource;
use App\Http\Resources\v2\SectionResource;
use App\Http\Resources\v2\SubjectResource;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;

class ExamController extends BaseApiController
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Get All Exams (قائمة الامتحانات/المراحل الدراسية)
     * 
     * يجلب قائمة بجميع الامتحانات أو المراحل الدراسية (مثل: الصف الأول الثانوي، اختبار القدرات العامة) المتاحة على المنصة.
     * يمكن التصفح بدون تسجيل دخول لزوار المنصة.
     * يدعم الفلترة عبر إرسال معلمات البحث.
     *
     * @queryParam search string optional نص للبحث عن امتحان بعينه بواسطة اسمه. Example: Math
     * @queryParam per_page integer optional عدد العناصر المطلوبة في الصفحة الواحدة. Example: 10
     *
     * @group Browsing / Exams (الامتحانات)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{exams: array}}
     * @response 500 array{status: int, message: string}
     */
    public function index(ExamIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $exams = $this->examService->getAllExams($filters);

            return $this->successResponse([
                'exams' => ExamResource::collection($exams)
            ], 'Exams retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exams: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Exam Details (تفاصيل الامتحان/المرحلة)
     * 
     * يجلب كافة التفاصيل المتاحة عن امتحان أو صف دراسي معين. يتضمن ذلك البيانات الأساسية،
     * والهيكل التابع له (بدون سرد ملايين الأسئلة المتعلقة به مباشرة للحفاظ على الأداء).
     *
     * @pathParam exam integer required المعرف الافتراضي للامتحان المُراد جلبه. Example: 1
     *
     * @group Browsing / Exams (الامتحانات)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{exam: array}}
     * @response 404 array{status: int, message: string}
     */
    public function show(Exam $exam): JsonResponse
    {
        try {
            $examDetails = $this->examService->getExamDetails($exam);

            return $this->successResponse([
                'exam' => new ExamDetailResource($examDetails)
            ], 'Exam details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Exam Subjects (المواد التابعة للامتحان)
     * 
     * يجلب قائمة بالمواد الدراسية (Subjects) المنسوبة إلى هذا الامتحان/المرحلة الدراسية.
     * على سبيل المثال، للصف الأول الثانوي سيرجع: (رياضيات، فيزياء، لغة عربية، الخ).
     *
     * @pathParam exam integer required المعرف الافتراضي للامتحان. Example: 1
     *
     * @group Browsing / Exams (الامتحانات)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{subjects: array}}
     * @response 404 array{status: int, message: string}
     */
    public function subjects(Exam $exam): JsonResponse
    {
        try {
            $subjects = $this->examService->getExamSubjects($exam);

            return $this->successResponse([
                'subjects' => SubjectResource::collection($subjects)
            ], 'Exam subjects retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam subjects: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Exam Sections (أقسام الامتحان)
     * 
     * يجلب قائمة بالأقسام الهرمية/الأجزاء (Sections) التي يتفرع إليها هذا الامتحان.
     * مفيد في بناء واجهات الفلترة الجانبية Sidebar للمستخدمين.
     *
     * @pathParam exam integer required المعرف الافتراضي للامتحان. Example: 1
     *
     * @group Browsing / Exams (الامتحانات)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{sections: array}}
     * @response 404 array{status: int, message: string}
     */
    public function sections(Exam $exam): JsonResponse
    {
        try {
            $sections = $this->examService->getExamSections($exam);

            return $this->successResponse([
                'sections' => SectionResource::collection($sections)
            ], 'Exam sections retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam sections: ' . $e->getMessage(), 500);
        }
    }
}
