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
     * Get student statistics for charts.
     *
     * @param Request $request
     * @return JsonResponse
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
