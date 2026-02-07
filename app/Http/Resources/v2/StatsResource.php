<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_students' => $this->resource['total_students'],
            'total_courses' => $this->resource['total_courses'],
            'average_progress' => $this->resource['average_progress'],
            'new_students_last_month' => $this->resource['new_students_last_month'],
            'active_enrollments' => $this->resource['active_enrollments'],
            'new_enrollments_this_week' => $this->resource['new_enrollments_this_week'],
            'completed_lessons' => $this->resource['completed_lessons'],
        ];
    }
}
