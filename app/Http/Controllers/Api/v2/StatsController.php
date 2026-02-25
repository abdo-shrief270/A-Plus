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
     * Get Platform Statistics
     *
     * Retrieve high-level platform statistics including total students, active courses, 
     * average progress, and active enrollments. The statistics are automatically scoped
     * based on the authenticated user's role (School or Parent).
     *
     * @unauthenticated false
     * @return JsonResponse
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
