<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'slug' => fake()->unique()->slug(),
            'image_path' => fake()->imageUrl(),
            'price' => fake()->randomFloat(2, 0, 500),
            'active' => true,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'total_hours' => fake()->numberBetween(10, 100),
            'lectures_count' => fake()->numberBetween(10, 50),
            'rating' => fake()->randomFloat(2, 3, 5),
        ];
    }
}
