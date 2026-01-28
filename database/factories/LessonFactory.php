<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $colors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899'];

        $titles = [
            'مقدمة في الجبر',
            'الهندسة الأساسية',
            'حساب المثلثات',
            'التفاضل والتكامل',
            'الإحصاء والاحتمالات',
            'المعادلات التفاضلية',
            'الأعداد المركبة',
            'المصفوفات والمحددات',
        ];

        return [
            'exam_id' => Exam::factory(),
            'title' => fake()->randomElement($titles),
            'description' => fake()->paragraph(),
            'logo' => null,
            'color' => fake()->randomElement($colors),
            'order' => fake()->numberBetween(1, 10),
            'duration_minutes' => fake()->randomElement([20, 30, 45, 60]),
            'is_active' => true,
        ];
    }
}
