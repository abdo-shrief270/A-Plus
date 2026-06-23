<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (int) $this->amount,
            'direction' => $this->amount < 0 ? 'debit' : 'credit',
            'type' => $this->type,
            'reference_type' => $this->reference_type ? class_basename($this->reference_type) : null,
            'reference_id' => $this->reference_id,
            'running_balance' => $this->running_balance ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
