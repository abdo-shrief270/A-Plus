<?php

namespace Database\Factories;

use App\Models\ExamSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_section_id' => ExamSection::factory(),
            'name' => 'فئة ' . $this->faker->word(),
            'description' => $this->faker->realText(50),
        ];
    }
}
