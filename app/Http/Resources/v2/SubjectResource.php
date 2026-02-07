<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'description' => $this->description,
            'questions_count' => $this->when(isset($this->questions_count), $this->questions_count, 0),
            'exam' => $this->when($this->relationLoaded('exam'), function () {
                return [
                    'id' => $this->exam->id,
                    'name' => $this->exam->name,
                ];
            }),
        ];
    }
}
