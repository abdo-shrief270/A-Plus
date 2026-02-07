<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeExamResource extends JsonResource
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
            'title' => $this->title,
            'is_active' => $this->is_active,
            'questions_count' => $this->whenLoaded('questions', function () {
                return $this->questions->count();
            }, 0),
            'questions' => QuestionDetailResource::collection($this->whenLoaded('questions')),
        ];
    }
}
