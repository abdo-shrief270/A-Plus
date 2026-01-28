<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LessonPage;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $exams = Exam::all();

        if ($exams->isEmpty()) {
            $this->command->warn('No exams found. Please run ExamSeeder first.');
            return;
        }

        foreach ($exams->take(2) as $exam) {
            // Create 5-7 lessons per exam
            $lessonCount = rand(5, 7);

            for ($i = 1; $i <= $lessonCount; $i++) {
                $lesson = Lesson::create([
                    'exam_id' => $exam->id,
                    'title' => $this->getLessonTitle($i),
                    'description' => $this->getLessonDescription($i),
                    'logo' => null,
                    'color' => $this->getColor($i),
                    'order' => $i,
                    'duration_minutes' => rand(20, 60),
                    'is_active' => true,
                ]);

                // Create 3-5 pages per lesson
                $pageCount = rand(3, 5);
                for ($j = 1; $j <= $pageCount; $j++) {
                    LessonPage::create([
                        'lesson_id' => $lesson->id,
                        'page_number' => $j,
                        'type' => $this->getPageType($j),
                        'title' => "صفحة {$j} - {$lesson->title}",
                        'content' => $this->getPageContent($this->getPageType($j), $j),
                        'is_required' => true,
                    ]);
                }
            }
        }

        $this->command->info('Lessons and pages seeded successfully!');
    }

    private function getLessonTitle(int $index): string
    {
        $titles = [
            'مقدمة في الموضوع',
            'المفاهيم الأساسية',
            'التطبيقات العملية',
            'الأمثلة المحلولة',
            'التمارين والمسائل',
            'المراجعة النهائية',
            'نصائح وإرشادات',
        ];

        return $titles[$index - 1] ?? "درس {$index}";
    }

    private function getLessonDescription(int $index): string
    {
        return "هذا الدرس يغطي الموضوعات الأساسية المطلوبة للامتحان. يرجى مراجعة جميع الصفحات بعناية.";
    }

    private function getColor(int $index): string
    {
        $colors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#14B8A6'];
        return $colors[($index - 1) % count($colors)];
    }

    private function getPageType(int $pageNumber): string
    {
        return match ($pageNumber) {
            1 => 'text',
            2 => 'image',
            3 => 'mixed',
            default => 'text',
        };
    }

    private function getPageContent(string $type, int $pageNumber): array
    {
        return match ($type) {
            'text' => [
                'body' => "<h2>محتوى الصفحة {$pageNumber}</h2><p>هذا نص توضيحي يشرح المفاهيم الأساسية للدرس. يمكن تعديل هذا المحتوى من لوحة التحكم.</p><p>النقاط الرئيسية:</p><ul><li>النقطة الأولى</li><li>النقطة الثانية</li><li>النقطة الثالثة</li></ul>",
            ],
            'image' => [
                'image_url' => 'https://via.placeholder.com/800x600/10B981/FFFFFF?text=صورة+توضيحية',
                'caption' => 'شرح توضيحي للصورة المعروضة',
            ],
            'question' => [
                'question_id' => 1,
                'instructions' => 'حل السؤال التالي بعناية واختر الإجابة الصحيحة',
            ],
            'mixed' => [
                'sections' => [
                    [
                        'type' => 'text',
                        'content' => '<p>مقدمة نصية للموضوع</p>',
                    ],
                    [
                        'type' => 'image',
                        'content' => 'https://via.placeholder.com/600x400/3B82F6/FFFFFF?text=رسم+توضيحي',
                    ],
                    [
                        'type' => 'text',
                        'content' => '<p>شرح إضافي بعد الصورة</p>',
                    ],
                ],
            ],
        };
    }
}
