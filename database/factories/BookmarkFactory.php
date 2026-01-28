<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'question_id' => Question::factory(),
        ];
    }
}
