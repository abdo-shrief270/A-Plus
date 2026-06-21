<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Exam;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'title' => 'أساسيات الرياضيات للقدرات العامة',
                'description' => 'كورس متكامل يغطي القواعد الأساسية في الجبر والهندسة والإحصاء، مع تدريبات منوعة لرفع مستوى الطالب.',
                'price' => 350,
                'level' => 'beginner',
                'total_hours' => 24,
                'lectures_count' => 18,
                'rating' => 4.6,
                'image_path' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=800&q=80',
            ],
            [
                'title' => 'اللفظي - فهم النصوص واستيعاب المقروء',
                'description' => 'تطوير مهارات تحليل النصوص والإجابة عن أسئلة الفهم والاستيعاب بأسلوب مدروس.',
                'price' => 280,
                'level' => 'beginner',
                'total_hours' => 16,
                'lectures_count' => 12,
                'rating' => 4.4,
                'image_path' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&q=80',
            ],
            [
                'title' => 'الكمي المتقدم - استراتيجيات حل الأسئلة',
                'description' => 'تركز على الأنماط المتقدمة في الجزء الكمي مع تدريب على إدارة الوقت أثناء الاختبار.',
                'price' => 450,
                'level' => 'advanced',
                'total_hours' => 32,
                'lectures_count' => 25,
                'rating' => 4.8,
                'image_path' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?w=800&q=80',
            ],
            [
                'title' => 'القياس الكامل - شامل الكمي واللفظي',
                'description' => 'حزمة متكاملة تجمع بين الجزأين الكمي واللفظي مع اختبارات محاكاة بنفس مستوى الاختبار الرسمي.',
                'price' => 600,
                'level' => 'intermediate',
                'total_hours' => 48,
                'lectures_count' => 36,
                'rating' => 4.9,
                'image_path' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&q=80',
            ],
            [
                'title' => 'مراجعة مكثفة قبل الاختبار',
                'description' => 'مراجعة سريعة لأهم المحاور مع نماذج مختصرة ومناقشة الأخطاء الشائعة.',
                'price' => 200,
                'level' => 'intermediate',
                'total_hours' => 12,
                'lectures_count' => 10,
                'rating' => 4.5,
                'image_path' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=800&q=80',
            ],
            [
                'title' => 'حل الاختبارات السابقة بالشرح',
                'description' => 'تحليل تفصيلي لاختبارات السنوات السابقة مع مناقشة طرق الحل المختلفة لكل سؤال.',
                'price' => 320,
                'level' => 'intermediate',
                'total_hours' => 20,
                'lectures_count' => 16,
                'rating' => 4.7,
                'image_path' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&q=80',
            ],
            [
                'title' => 'مهارات إدارة الوقت في الاختبارات',
                'description' => 'استراتيجيات عملية للتعامل مع ضغط الوقت أثناء أداء الاختبار وكيفية أولوية الأسئلة.',
                'price' => 0,
                'level' => 'beginner',
                'total_hours' => 4,
                'lectures_count' => 5,
                'rating' => 4.3,
                'image_path' => 'https://images.unsplash.com/photo-1495364141860-b0d03eccd065?w=800&q=80',
            ],
            [
                'title' => 'التحصيلي - علوم وفيزياء',
                'description' => 'مراجعة شاملة لمنهج العلوم والفيزياء مع نماذج تطبيقية وأسئلة تحاكي الاختبار التحصيلي.',
                'price' => 500,
                'level' => 'advanced',
                'total_hours' => 36,
                'lectures_count' => 28,
                'rating' => 4.8,
                'image_path' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=800&q=80',
            ],
        ];

        $examIds = Exam::query()->pluck('id')->all();

        foreach ($courses as $data) {
            $slug = 'course-' . substr(md5($data['title']), 0, 10);

            $course = Course::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'active' => true,
                    'start_date' => now()->subDays(rand(1, 30)),
                    'end_date' => now()->addMonths(rand(1, 6)),
                ])
            );

            if (!empty($examIds)) {
                $course->exams()->syncWithoutDetaching($examIds);
            }
        }

        $this->command?->info('Seeded ' . count($courses) . ' courses.');
    }
}
