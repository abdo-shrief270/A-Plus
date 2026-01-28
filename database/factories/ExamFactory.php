<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'اختبار ' . $this->faker->word(),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }
}
