<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseApiController
{
    /**
     * List Plans (قائمة الباقات)
     *
     * يجلب الباقات النشطة المتاحة. يدعم الفلترة بالنوع (subscription أو pack).
     *
     * @queryParam type string optional Example: subscription
     * @queryParam per_page integer optional Default 20
     *
     * @group Dashboard / Plans (الباقات)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query()
            ->where('is_active', true)
            ->orderBy('price');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $plans = $query->paginate($request->input('per_page', 20));

        return $this->successResponse(
            PlanResource::collection($plans)->response()->getData(true),
            'Plans retrieved successfully'
        );
    }

    public function show(int $plan): JsonResponse
    {
        $found = Plan::where('is_active', true)->find($plan);
        if (!$found) {
            return $this->errorResponse('Plan not found', 404);
        }

        return $this->successResponse(
            new PlanResource($found),
            'Plan retrieved successfully'
        );
    }
}
