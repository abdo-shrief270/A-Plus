<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\RevisionMetricsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags المراجعة (Revision)
 */
class RevisionController extends BaseApiController
{
    public function __construct(protected RevisionMetricsService $service)
    {
    }

    /**
     * Student Revision Metrics (إحصائيات المراجعة للطالب)
     *
     * يجلب بيانات المراجعة الكاملة للطالب الحالي:
     * - إجمالي الأسئلة في الاختبار
     * - عدد الأسئلة التي حلّها (مرة واحدة على الأقل) ونسبتها
     * - دقة الإجابة (الصحيحة / المُجابة)
     * - عدد المحاولات الإجمالية والنقاط المُكتسبة
     * - تفصيل لكل قسم وكل تصنيف فرعي مع أشرطة تقدّم
     * - آخر 5 محاولات
     *
     * @group Revision (المراجعة)
     */
    public function metrics(): JsonResponse
    {
        $user = auth('api')->user();
        $student = $user?->student;

        if (!$student) {
            return $this->errorResponse('Revision metrics are only available for students', Response::HTTP_FORBIDDEN);
        }

        return $this->successResponse(
            $this->service->bundle($student),
            'Revision metrics retrieved successfully'
        );
    }
}
