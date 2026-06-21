<?php

namespace App\Http\Resources\v2;

use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a QuizSessionQuestion row (expects question.answers + question.type
 * eager-loaded, and the parent session available via ->session or set with
 * setSession()).
 *
 * This resource is the SINGLE reveal gate for quiz correctness data:
 * is_correct / correct_answer_id / explanation appear only when the session
 * is finalized, or in tutor mode after this specific question was answered.
 * Never swap it for QuestionDetailResource (which always exposes explanation).
 */
class QuizQuestionResource extends JsonResource
{
    protected ?QuizSession $quizSession = null;

    public function setSession(QuizSession $session): static
    {
        $this->quizSession = $session;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->quizSession ?? $this->session;
        $question = $this->question;

        $revealed = $session->isFinalized()
            || ($session->mode === QuizSession::MODE_TUTOR && $this->isAnswered());

        $data = [
            'order' => $this->order,
            'chosen_answer_id' => $this->answer_id,
            'answered_at' => $this->answered_at,
            'question' => [
                'id' => $question->id,
                'text' => $question->text,
                'difficulty' => $question->difficulty,
                'comparison' => ($question->comparison_value_1 || $question->comparison_image_1
                    || $question->comparison_value_2 || $question->comparison_image_2)
                    ? [
                        'value_1' => ['text' => $question->comparison_value_1, 'image' => $question->comparison_image_1],
                        'value_2' => ['text' => $question->comparison_value_2, 'image' => $question->comparison_image_2],
                    ]
                    : null,
                'type' => $question->relationLoaded('type') && $question->type ? [
                    'id' => $question->type->id,
                    'name' => $question->type->name,
                ] : null,
                'answers' => AnswerResource::collection($question->answers),
            ],
        ];

        if ($revealed) {
            $data['is_correct'] = $this->is_correct;
            $data['correct_answer_id'] = $question->answers->firstWhere('is_correct', true)?->id;
            $data['explanation'] = [
                'text' => $question->explanation_text,
                'video_url' => $question->explanation_video_url,
            ];
        }

        return $data;
    }

    /**
     * Collection helper that injects the parent session into every item so
     * the reveal logic never falls back to a lazy session load.
     */
    public static function collectionFor(QuizSession $session, $rows)
    {
        return $rows->map(fn ($row) => (new static($row))->setSession($session));
    }
}
