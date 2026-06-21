<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lesson detail. Pages are included only when eager-loaded (lesson view),
 * not in list contexts. The student's progress is attached when available.
 */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'title' => $this->title,
            'description' => $this->description,
            'logo' => $this->logo,
            'color' => $this->color,
            'order' => $this->order,
            'duration_minutes' => $this->duration_minutes,
            'pages_count' => $this->whenCounted('pages'),
            'pages' => LessonPageResource::collection($this->whenLoaded('pages')),
            'progress' => $this->when(
                $this->relationLoaded('studentProgress'),
                fn () => $this->resolveProgress()
            ),
        ];
    }

    private function resolveProgress(): ?array
    {
        $p = $this->studentProgress->first();
        if (!$p) {
            return null;
        }

        return [
            'status' => $p->status,
            'scheduled_date' => optional($p->scheduled_date)->toDateString(),
            'started_at' => optional($p->started_at)->toIso8601String(),
            'completed_at' => optional($p->completed_at)->toIso8601String(),
            'time_spent_minutes' => $p->time_spent_minutes,
        ];
    }
}
