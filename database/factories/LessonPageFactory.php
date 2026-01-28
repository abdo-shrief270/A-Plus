<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPageFactory extends Factory
{
    protected $model = LessonPage::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['text', 'image', 'mixed']);

        return [
            'lesson_id' => Lesson::factory(),
            'page_number' => 1,
            'type' => $type,
            'title' => fake()->sentence(3),
            'content' => $this->generateContent($type),
            'is_required' => true,
        ];
    }

    protected function generateContent(string $type): array
    {
        return match ($type) {
            'text' => [
                'body' => fake()->paragraphs(3, true),
            ],
            'image' => [
                'image_url' => 'https://via.placeholder.com/800x600',
                'caption' => fake()->sentence(),
            ],
            'question' => [
                'question_id' => 1,
                'instructions' => 'حل السؤال التالي بعناية',
            ],
            'mixed' => [
                'sections' => [
                    ['type' => 'text', 'content' => fake()->paragraph()],
                    ['type' => 'image', 'content' => 'https://via.placeholder.com/600x400'],
                ],
            ],
        };
    }
}
