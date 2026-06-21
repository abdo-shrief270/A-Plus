<?php

namespace App\Http\Resources\v2;

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
        return [
            'id' => $this->id,
            'text' => $this->text,
            'difficulty' => $this->difficulty,
            'is_new' => $this->is_new,
            'comparison' => $this->when(
                $this->comparison_value_1 || $this->comparison_image_1 || $this->comparison_value_2 || $this->comparison_image_2,
                [
                    'value_1' => [
                        'text' => $this->comparison_value_1,
                        'image' => $this->comparison_image_1,
                    ],
                    'value_2' => [
                        'text' => $this->comparison_value_2,
                        'image' => $this->comparison_image_2,
                    ],
                ]
            ),
            'explanation' => [
                'text' => $this->explanation_text,
                'video_url' => $this->explanation_video_url,
            ],
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'previous_question_id' => $this->previous_question_id ?? null,
            'next_question_id' => $this->next_question_id ?? null,
            'type' => $this->whenLoaded('type', fn() => [
                'id' => $this->type->id,
                'name' => $this->type->name ?? null,
            ]),
            'breadcrumb' => $this->breadcrumb(),
            'is_bookmarked' => $this->isBookmarked(),
        ];
    }

    protected function isBookmarked(): bool
    {
        $student = auth('api')->user()?->student;
        if (!$student) return false;
        return \App\Models\Bookmark::where('student_id', $student->id)
            ->where('question_id', $this->id)
            ->exists();
    }

    /**
     * Build a section/category/article trail for the question header.
     * Picks the first category (and the first article that maps to it) so the
     * UI has a single, stable path to render.
     */
    protected function breadcrumb(): ?array
    {
        $category = $this->relationLoaded('categories') ? $this->categories->first() : null;

        $article = null;
        if ($this->relationLoaded('articles')) {
            $article = $category
                ? ($this->articles->first(function ($a) use ($category) {
                    return ($a->section_category_id ?? null) === $category->id;
                }) ?? $this->articles->first())
                : $this->articles->first();
        }

        // Article-only questions carry no direct category pivot — derive the
        // trail from the article's own category instead.
        if (!$category && $article && $article->relationLoaded('category')) {
            $category = $article->category;
        }

        if (!$category) return null;

        $section = $category->relationLoaded('section') ? $category->section : null;

        return [
            'section' => $section ? [
                'id' => $section->id,
                'name' => $section->name,
            ] : null,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'article' => $article ? [
                'id' => $article->id,
                'title' => $article->title,
            ] : null,
        ];
    }
}
