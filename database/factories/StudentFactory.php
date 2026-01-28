<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_id' => Exam::factory(),
            'exam_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'id_number' => $this->faker->unique()->numerify('1#########'),
        ];
    }
}
