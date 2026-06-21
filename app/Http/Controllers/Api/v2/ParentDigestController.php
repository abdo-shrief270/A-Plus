<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\ParentWeeklyDigestService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags ولي الأمر (Parent)
 */
class ParentDigestController extends BaseApiController
{
    public function __construct(protected ParentWeeklyDigestService $service)
    {
    }

    /**
     * Children Weekly Summary (ملخص الأبناء الأسبوعي)
     *
     * يعيد ملخص نشاط كل طفل خلال آخر 7 أيام (أو مدة مخصصة) لعرضه في لوحة ولي الأمر.
     *
     * @queryParam days integer optional نافذة الملخص بالأيام. Default 7.
     *
     * @group Parent (ولي الأمر)
     */
    public function weekly(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->studentParent()->exists()) {
            return $this->errorResponse('This summary is only available for parents', Response::HTTP_FORBIDDEN);
        }

        $days = min(30, max(1, (int) $request->input('days', 7)));
        $since = Carbon::now()->subDays($days);

        return $this->successResponse([
            'period_days' => $days,
            'children' => $this->service->summariesForParent($user, $since),
        ], 'Weekly summary retrieved successfully');
    }
}
