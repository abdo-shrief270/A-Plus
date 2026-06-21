<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->active,
            // Counts for the exam cards (present when loaded via withCount).
            'sections_count' => $this->whenCounted('sections'),
            'subjects_count' => $this->whenCounted('categories'),
        ];
    }
}
