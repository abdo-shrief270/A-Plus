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
     * Get Trending Courses
     *
     * Retrieve a list of the most popular courses on the platform, ordered by
     * the highest number of active student enrollments. Useful for dashboard widgets.
     *
     * @unauthenticated false
     * @param Request $request
     * @return JsonResponse
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
