<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\StudentStatsResource;
use App\Services\StudentStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentStatsController extends BaseApiController
{
    public function __construct(
        protected StudentStatsService $studentStatsService
    ) {
    }

    /**
     * Get Student Statistics Charts (رسوم بيانية لتقدم الطلاب)
     *
     * يجلب بيانات متسلسلة زمنياً (Time-series) لإحصائيات تقدم الطلاب ليتم استخدامها في رسم المخططات البيانية (Charts) بالواجهة الأمامية.
     * يدعم فترات زمنية محددة. ويُفلتر النتائج حصرًا لدور المستخدم (ولي الأمر لطلابه، أو المدرسة لطلاب مدرستها).
     *
     * @queryParam period string optional الفترة الزمنية المطلوبة للمخطط البياني. القيم المتاحة: `week`, `month`, `3months`, `year`. Example: month
     *
     * @group Dashboard / Analytics (الإحصائيات ولوحة التحكم)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{labels: array, dataset: array}}
     * @response 400 array{status: int, message: string} - في حال طلب فترة غير صحيحة
     * @response 401 array{status: int, message: string}
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        if (!in_array($period, ['week', 'month', '3months', 'year'])) {
            return $this->errorResponse('Invalid period. Use: week, month, 3months, year', 400);
        }

        $user = auth('api')->user();
        $stats = $this->studentStatsService->getStudentStats($period, $user);

        return $this->successResponse(
            new StudentStatsResource($stats),
            'Student statistics retrieved successfully'
        );
    }
}
