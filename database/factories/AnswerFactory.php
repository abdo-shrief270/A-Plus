<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text' => $this->faker->realText(30),
            'image_path' => null,
            'is_correct' => false,
            'order' => $this->faker->numberBetween(1, 4),
        ];
    }
}
