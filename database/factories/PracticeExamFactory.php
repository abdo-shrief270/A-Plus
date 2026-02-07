<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PracticeExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'اختبار تجريبي ' . $this->faker->word(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }
}
