<?php

namespace App\Http\Resources\v2;

use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $questionService = app(QuestionService::class);
        $context = $questionService->getQuestionContext($this->resource);

        return [
            'id' => $this->id,
            'text' => $this->text,
            'image_path' => $this->image_path,
            'difficulty' => $this->difficulty,
            'is_new' => $this->is_new,
            'explanation' => [
                'text' => $this->explanation_text,
                'image_path' => $this->explanation_text_image_path,
                'video_url' => $this->explanation_video_url,
            ],
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'belongs_to' => $context,
            'type' => $this->whenLoaded('type', fn() => [
                'id' => $this->type->id,
                'name' => $this->type->name ?? null,
            ]),
        ];
    }
}
