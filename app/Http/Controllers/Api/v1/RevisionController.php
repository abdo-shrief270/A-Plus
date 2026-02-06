<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevisionController extends BaseApiController
{
    public function stats(Request $request)
    {
        $user = auth('api')->user();

        $stats = [
            'total_answered' => StudentAnswer::where('user_id', $user->id)->count(),
            'total_correct' => StudentAnswer::where('user_id', $user->id)->where('is_correct', true)->count(),
        ];

        if ($stats['total_answered'] > 0) {
            $stats['accuracy'] = round(($stats['total_correct'] / $stats['total_answered']) * 100, 2);
        } else {
            $stats['accuracy'] = 0;
        }

        // Breakdown by Date (last 7 days?)
        // Or by ID (latest) via history endpoint.

        return $this->successResponse($stats, 'User stats retrieved');
    }

    public function history(Request $request)
    {
        $user = auth('api')->user();

        $history = StudentAnswer::with(['question', 'answer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return $this->successResponse($history, 'Answer history retrieved');
    }
}
