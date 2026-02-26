<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\StatsResource;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends BaseApiController
{
    public function __construct(
        protected StatsService $statsService
    ) {
    }

    /**
     * Get Platform Statistics (إحصائيات المنصة)
     *
     * يجلب إحصائيات عامة وعالية المستوى للمنصة تتضمن (إجمالي الطلاب، الدورات النشطة، متوسط التقدم، والتسجيلات النشطة).
     * يتم فلترة البيانات المرجعة تلقائياً بناءً على دور المستخدم المسجل الدخول (مدير مدرسة School سيراها فقط لطلابه، أما ولي الأمر Parent سيراها لأبنائه فحسب).
     *
     * @group Dashboard / Analytics (الإحصائيات ولوحة التحكم)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{total_students: int, active_courses: int, active_enrollments: int, average_progress: int}}
     * @response 401 array{status: int, message: string}
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $stats = $this->statsService->getPlatformStats($user);

        return $this->successResponse(
            new StatsResource($stats),
            'Platform statistics retrieved successfully'
        );
    }
}
