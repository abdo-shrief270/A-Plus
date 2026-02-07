<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalPoints = $this->wallet?->balance ?? 0;
        $totalScore = $this->scores?->sum('score') ?? 0;

        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'user_name' => $this->user?->user_name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'gender' => $this->user?->gender,
            'id_number' => $this->id_number,
            'exam_id' => $this->exam_id,
            'exam_name' => $this->exam?->name,
            'exam_date' => $this->exam_date?->format('Y-m-d'),
            'league' => $this->league ? [
                'id' => $this->league->id,
                'name' => $this->league->name,
                'icon' => $this->league->icon,
            ] : null,
            'total_score' => $totalScore,
            'total_points' => $totalPoints,
            'joined_at' => $this->created_at?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
