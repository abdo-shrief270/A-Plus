<?php

namespace Database\Factories;

use App\Models\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'text' => $this->faker->realText(100) . '؟',
            'image_path' => null,
            'explanation_text' => $this->faker->realText(200),
            'question_type_id' => QuestionType::factory(),
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }
}
