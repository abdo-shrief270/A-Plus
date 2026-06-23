<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\WalletTransactionResource;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags المحفظة (Wallet / Points)
 */
class WalletController extends BaseApiController
{
    /**
     * Points history (سجل النقاط)
     *
     * Paginated wallet ledger with a per-row running balance and the current
     * balance. Students only.
     *
     * @queryParam per_page integer optional Default 15 (1-100).
     * @queryParam type string optional Filter by transaction type.
     */
    public function transactions(Request $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Wallet is only available for students', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'type' => ['sometimes', 'string', 'max:64'],
        ]);

        $wallet = Wallet::firstOrCreate(['student_id' => $student->id], ['balance' => 0]);

        // Lifetime cumulative balance keyed by transaction id so it stays correct
        // even when the result is filtered/paginated.
        $running = [];
        $sum = 0;
        foreach ($wallet->transactions()->orderBy('created_at')->orderBy('id')->get(['id', 'amount']) as $t) {
            $sum += (int) $t->amount;
            $running[$t->id] = $sum;
        }

        $query = $wallet->transactions()->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $paginated = $query->paginate((int) $request->input('per_page', 15));
        $paginated->getCollection()->transform(function ($t) use ($running) {
            $t->running_balance = $running[$t->id] ?? null;

            return $t;
        });

        $data = WalletTransactionResource::collection($paginated)->response()->getData(true);
        $data['current_balance'] = (int) $wallet->balance;

        return $this->successResponse($data, 'Wallet transactions retrieved successfully');
    }
}
