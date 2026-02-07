<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingCourseResource extends JsonResource
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
            'price' => $this->price,
            'level' => $this->level,
            'rating' => $this->rating,
            'total_hours' => $this->total_hours,
            'lectures_count' => $this->lectures_count,
            'enrollments_count' => $this->enrollments_count ?? 0,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
        ];
    }
}
