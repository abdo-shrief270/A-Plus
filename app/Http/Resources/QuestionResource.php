<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->type ? $this->type->name : 'unknown';

        return [
            'id' => $this->id,
            'text' => $this->text,
            'image' => $this->image_path,
            'type' => $type,
            'points_cost' => $this->points_cost,
            'difficulty' => $this->difficulty,
            'is_new' => $this->is_new,
            'explanation' => [
                'text' => $this->explanation_text,
                'image' => $this->explanation_text_image_path,
                'video' => $this->explanation_video_url,
            ],
            // Dynamic data based on type
            'data' => $this->getQuestionData($type),
            // User context (if authenticated)
            'user_state' => $this->getUserState(),
        ];
    }

    protected function getQuestionData(string $type): array
    {
        // Add specific formatting logic here.
        // For MCQ, we list answers.

        if (in_array(strtolower($type), ['mcq', 'true_false', 'truefalse'])) {
            return [
                'options' => $this->answers->map(function ($ans) {
                    return [
                        'id' => $ans->id,
                        'text' => $ans->text,
                        'image' => $ans->image_path, // Assuming Answer model has image_path logic
                        'order' => $ans->order,
                    ];
                }),
            ];
        }

        // Default fallback
        return [
            'raw_answers' => $this->answers
        ];
    }

    protected function getUserState(): ?array
    {
        $user = auth('api')->user();
        if (!$user) {
            return null;
        }

        // Check if user has answered this question (e.g. latest answer)
        // Optimization: Eager load this in Controller
        // For now, assuming relation 'studentAnswers' on Question or User.
        // Let's assume we can fetch it via relationship on Question if defined, or query.

        return [
            'unlocked' => true, // TODO: Check points unlocked logic
            'solved' => false, // TODO: Check if answered
            'last_answer' => null,
        ];
    }
}
