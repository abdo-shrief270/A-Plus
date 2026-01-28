<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamSubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'name' => 'مادة ' . $this->faker->word(),
            'description' => $this->faker->realText(50),
        ];
    }
}
