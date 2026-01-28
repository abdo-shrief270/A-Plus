<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'question_id' => Question::factory(),
            'answer_id' => Answer::factory(),
            'is_correct' => $this->faker->boolean(),
        ];
    }
}
