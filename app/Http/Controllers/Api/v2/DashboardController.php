<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\CourseResource;
use App\Http\Resources\v2\EnrollmentResource;
use App\Http\Resources\v2\StudentResource;
use App\Http\Resources\v2\SubscriptionResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    /**
     * Get Dashboard Bundle (لوحة تحكم - بيانات شاملة)
     *
     * يجلب بيانات شاملة لصفحة لوحة التحكم في طلب واحد:
     * - إحصائيات (عدد الطلاب، التسجيلات النشطة، الاشتراكات النشطة، إلخ).
     * - أحدث 5 طلاب مرتبطين بالمستخدم.
     * - أحدث 5 تسجيلات في الكورسات.
     * - أحدث 5 اشتراكات في الباقات.
     * - 5 من الكورسات الأكثر رواجاً.
     *
     * يفلتر تلقائياً بحسب دور المستخدم: ولي الأمر يرى أبناءه، المدرسة طلابها،
     * الطالب نفسه فقط.
     *
     * @group Dashboard / Overview (لوحة التحكم)
     * @unauthenticated false
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $bundle = $this->dashboardService->bundle($user);

        return $this->successResponse([
            'stats' => $bundle['stats'],
            'recent_students' => StudentResource::collection($bundle['recent_students']),
            'recent_enrollments' => EnrollmentResource::collection($bundle['recent_enrollments']),
            'recent_subscriptions' => SubscriptionResource::collection($bundle['recent_subscriptions']),
            'trending_courses' => CourseResource::collection($bundle['trending_courses']),
        ], 'Dashboard bundle retrieved successfully');
    }
}
