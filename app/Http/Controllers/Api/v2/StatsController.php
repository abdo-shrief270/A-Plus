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
     * Get platform-wide statistics.
     *
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
