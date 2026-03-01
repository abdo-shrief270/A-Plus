<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'articles_count' => $this->when(isset($this->articles_count), $this->articles_count, 0),
            'has_articles' => $this->when(isset($this->articles_count), $this->articles_count > 0, false),
            'section' => $this->when($this->relationLoaded('section'), function () {
                return [
                    'id' => $this->section->id,
                    'name' => $this->section->name,
                ];
            }),
        ];
    }
}
