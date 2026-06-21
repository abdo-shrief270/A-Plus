<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'image_path' => $this->course->image_path,
                'level' => $this->course->level,
                'price' => (float) $this->course->price,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'user_name' => $this->user->user_name,
                'student' => $this->user->relationLoaded('student') && $this->user->student ? [
                    'id' => $this->user->student->id,
                ] : null,
            ]),
        ];
    }
}
