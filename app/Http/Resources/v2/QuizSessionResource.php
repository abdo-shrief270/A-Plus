<?php

namespace App\Http\Resources\v2;

use App\Models\ExamSection;
use App\Models\SectionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Summary view of a quiz session (history rows, active banner, 409 payloads).
 */
class QuizSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $remaining = null;
        if ($this->deadline_at && $this->isInProgress()) {
            $remaining = max(0, (int) now()->diffInSeconds($this->deadline_at, false));
        }

        return [
            'id' => $this->id,
            'mode' => $this->mode,
            'source' => $this->source,
            'difficulty' => $this->difficulty,
            'question_count' => $this->question_count,
            'time_limit_seconds' => $this->time_limit_seconds,
            'status' => $this->status,
            'is_daily_challenge' => $this->challenge_date !== null,
            'is_simulation' => (bool) $this->is_simulation,
            'practice_exam' => $this->when($this->practice_exam_id !== null, fn () => [
                'id' => $this->practice_exam_id,
                'title' => $this->practiceExam?->title,
            ]),
            'challenge_id' => $this->challenge_id,
            'challenge_code' => $this->when(
                $this->challenge_id !== null && $this->relationLoaded('challenge'),
                fn () => $this->challenge?->invite_code
            ),
            'correct_count' => $this->correct_count,
            'incorrect_count' => $this->incorrect_count,
            'skipped_count' => $this->skipped_count,
            'score_percent' => $this->score_percent !== null ? (float) $this->score_percent : null,
            // Prefer the withCount() value when the query preloaded it (history
            // lists) — falls back to a per-row count for single-session payloads.
            'answered_count' => $this->getAttribute('answered_count')
                ?? $this->questions()->whereNotNull('answered_at')->count(),
            'remaining_seconds' => $remaining,
            'started_at' => $this->started_at,
            'deadline_at' => $this->deadline_at,
            'completed_at' => $this->completed_at,
            'scope' => [
                'sections' => $this->resolveNames(ExamSection::class, $this->section_ids ?? []),
                'categories' => $this->resolveNames(SectionCategory::class, $this->category_ids ?? []),
            ],
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    protected function resolveNames(string $model, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $model::whereIn('id', $ids)
            ->get(['id', 'name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
            ->all();
    }
}
