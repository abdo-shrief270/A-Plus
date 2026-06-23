<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    /**
     * Randomize answer order at the serialization boundary so every response
     * returns options in a different order while answer ids stay correct for
     * grading/reveal. Applies to all v2 resources that serialize answers
     * through AnswerResource::collection() (questions, quizzes, etc.).
     */
    public static function collection($resource)
    {
        if ($resource instanceof \Illuminate\Support\Collection || is_array($resource)) {
            $resource = collect($resource)->shuffle()->values();
        }

        return parent::collection($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'image_path' => $this->image_path,
//            'is_correct' => $this->is_correct,
            'order' => $this->order,
        ];
    }
}
