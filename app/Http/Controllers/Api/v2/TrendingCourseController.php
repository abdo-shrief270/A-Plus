<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\TrendingCourseResource;
use App\Services\TrendingCourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrendingCourseController extends BaseApiController
{
    public function __construct(
        protected TrendingCourseService $trendingCourseService
    ) {
    }

    /**
     * Get Trending Courses (الدورات الأكثر إقبالاً)
     *
     * يجلب قائمة مقسمة بأكثر الدورات شعبية على المنصة استناداً إلى عدد الطلاب النشطين المسجلين فيها.
     * يستخدم عادًة لعرض قسم (الدورات الأكثر طلباً) في الواجهة الرئيسية.
     *
     * @queryParam limit integer optional العدد الأقصى للدورات في القائمة الراجعة (الافتراضي 10). Example: 5
     *
     * @group Dashboard / Analytics (الإحصائيات ولوحة التحكم)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array}
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $courses = $this->trendingCourseService->getTrendingCourses((int) $limit);

        return $this->successResponse(
            TrendingCourseResource::collection($courses),
            'Trending courses retrieved successfully'
        );
    }
}
