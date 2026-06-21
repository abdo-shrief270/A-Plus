<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'price' => (float) $this->price,
            'points' => (int) $this->points,
            'duration_days' => $this->duration_days,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
