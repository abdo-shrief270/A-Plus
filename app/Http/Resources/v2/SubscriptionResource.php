<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'plan_id' => $this->plan_id,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => $this->status,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->user?->name,
                'user_name' => $this->student->user?->user_name,
                'gender' => $this->student->user?->gender,
            ]),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'type' => $this->plan->type,
                'price' => (float) $this->plan->price,
                'points' => (int) $this->plan->points,
                'duration_days' => $this->plan->duration_days,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
