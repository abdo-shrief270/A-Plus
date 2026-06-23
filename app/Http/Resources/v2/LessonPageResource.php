<?php

namespace App\Http\Resources\v2;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single lesson page. The `content` shape depends on `type`:
 *  - text:     { body }
 *  - image:    { image_url, caption }
 *  - question: { question, instructions }   (question_id resolved to payload)
 *  - mixed:    { sections: [{ type, content }] }
 *
 * Question pages are answered live through the same flow as the question bank
 * (POST /v2/questions/answer) — charging, points, and revision all count — so
 * the correct answer is NOT revealed up front; the answer endpoint returns it.
 */
class LessonPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_number' => $this->page_number,
            'type' => $this->type,
            'title' => $this->title,
            'is_required' => (bool) $this->is_required,
            'content' => $this->resolveContent(),
        ];
    }

    private function resolveContent(): array
    {
        $content = $this->content ?? [];

        if ($this->type === 'question') {
            $question = isset($content['question_id'])
                ? Question::with(['answers' => fn ($q) => $q->orderBy('order'), 'type'])->find($content['question_id'])
                : null;

            return [
                'instructions' => $content['instructions'] ?? null,
                // Bank-answerable shape: no is_correct revealed; the answer
                // endpoint returns correctness + the correct answer id.
                'question' => $question ? [
                    'id' => $question->id,
                    'text' => $question->text,
                    'image_path' => $question->image_path,
                    'difficulty' => $question->difficulty,
                    'comparison' => ($question->comparison_value_1 || $question->comparison_image_1
                        || $question->comparison_value_2 || $question->comparison_image_2)
                        ? [
                            'value_1' => ['text' => $question->comparison_value_1, 'image_path' => $question->comparison_image_1],
                            'value_2' => ['text' => $question->comparison_value_2, 'image_path' => $question->comparison_image_2],
                        ]
                        : null,
                    'explanation' => [
                        'text' => $question->explanation_text,
                        'image_path' => $question->explanation_image_path ?? null,
                        'video_url' => $question->explanation_video_url,
                    ],
                    'type' => $question->relationLoaded('type') && $question->type ? [
                        'id' => $question->type->id,
                        'name' => $question->type->name,
                    ] : null,
                    'answers' => $question->answers->shuffle()->map(fn ($a) => [
                        'id' => $a->id,
                        'text' => $a->text,
                        'image_path' => $a->image_path,
                        'order' => $a->order,
                    ])->values()->all(),
                ] : null,
            ];
        }

        // text / image / mixed pass through their stored JSON as-is.
        return is_array($content) ? $content : [];
    }
}
