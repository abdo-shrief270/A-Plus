<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\StudyPlanService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الخطة الدراسية (Study Plan)
 *
 * A student's lessons distributed across days up to their exam date.
 * Generated lazily on first access from the exam's active lessons.
 */
class StudyPlanController extends BaseApiController
{
    public function __construct(protected StudyPlanService $studyPlan)
    {
    }

    /**
     * My Study Plan (خطتي الدراسية)
     *
     * يعيد ملخص التقدّم ودروس الطالب مجمّعة حسب اليوم المجدول. تُنشأ الخطة
     * تلقائياً عند أول طلب اعتماداً على دروس الاختبار وتاريخه.
     *
     * @group Study Plan (الخطة الدراسية)
     */
    public function show(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Study plan is only available for students', Response::HTTP_FORBIDDEN);
        }

        if (!$student->exam_id || !$student->exam_date) {
            return $this->successResponse([
                'available' => false,
                'reason' => 'missing_exam',
                'message' => 'يجب تحديد الاختبار وتاريخه من ملفك الشخصي لإنشاء خطة دراسية.',
            ], 'Study plan unavailable');
        }

        $generated = $this->studyPlan->ensurePlan($student);
        if (!$generated) {
            return $this->successResponse([
                'available' => false,
                'reason' => 'no_lessons',
                'message' => 'لا توجد دروس متاحة لهذا الاختبار بعد.',
            ], 'Study plan unavailable');
        }

        return $this->successResponse([
            'available' => true,
            'summary' => $this->studyPlan->getStudyPlanSummary($student),
            'days' => $this->studyPlan->getGroupedPlan($student),
        ], 'Study plan retrieved successfully');
    }

    /**
     * Regenerate Plan (إعادة إنشاء الخطة)
     *
     * يحذف الخطة الحالية ويعيد توزيع الدروس على الأيام المتبقية حتى الاختبار.
     * تُفقد حالة "قيد التنفيذ" لكن الدروس المكتملة تبقى محسوبة عبر إعادة الجدولة.
     *
     * @group Study Plan (الخطة الدراسية)
     */
    public function regenerate(): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Study plan is only available for students', Response::HTTP_FORBIDDEN);
        }

        $result = $this->studyPlan->generateStudyPlan($student);
        if (!($result['success'] ?? false)) {
            return $this->errorResponse($result['message'] ?? 'تعذّر إنشاء الخطة.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->successResponse([
            'available' => true,
            'summary' => $this->studyPlan->getStudyPlanSummary($student),
            'days' => $this->studyPlan->getGroupedPlan($student),
        ], 'Study plan regenerated successfully');
    }
}
