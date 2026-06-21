<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image_path,
            'price' => (float) $this->price,
            'level' => $this->level,
            'rating' => (float) $this->rating,
            'total_hours' => (int) $this->total_hours,
            'lectures_count' => (int) $this->lectures_count,
            'enrollments_count' => $this->enrollments_count ?? null,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'exams' => $this->whenLoaded('exams', fn () => $this->exams->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
            ])),
        ];
    }
}
