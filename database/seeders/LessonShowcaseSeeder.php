<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonPage;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo lessons that exercise every LessonPage type (text / image / question /
 * mixed) so the student-facing lesson viewer can be tested end to end.
 *
 * Idempotent: lessons are upserted by (exam_id, title) and their pages are
 * rebuilt each run. Targets the first student's exam by default; override with
 * `php artisan db:seed --class=LessonShowcaseSeeder` after setting EXAM_ID env.
 */
class LessonShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $examId = (int) (env('EXAM_ID') ?: optional(Student::whereNotNull('exam_id')->first())->exam_id);
        if (!$examId) {
            $this->command?->warn('No exam found — skipping lesson showcase.');

            return;
        }

        // Two real questions from this exam for the question/mixed pages.
        $questionIds = Question::whereHas('categories.section', fn ($q) => $q->where('exam_id', $examId))
            ->limit(2)->pluck('id')->all();
        $q1 = $questionIds[0] ?? Question::value('id');
        $q2 = $questionIds[1] ?? $q1;

        $img = 'https://picsum.photos/seed/aplus-lesson/800/400';
        $img2 = 'https://picsum.photos/seed/aplus-diagram/700/360';

        $lessons = [
            [
                'title' => '📘 جولة في أنواع الصفحات',
                'description' => 'درس تجريبي يعرض كل أنواع صفحات الدرس: نص، صورة، سؤال، ومختلط.',
                'color' => '#10B981',
                'pages' => [
                    [
                        'type' => 'text',
                        'title' => 'صفحة نصية',
                        'content' => ['body' => "## مرحباً بك في الدرس\n\nهذه **صفحة نصية** تدعم تنسيق Markdown:\n\n- نقطة أولى\n- نقطة ثانية\n- نقطة ثالثة\n\n> اقتباس توضيحي مهم.\n\nويمكن أيضاً عرض معادلات أو روابط مثل [موقع المنصة](https://apls-edu.com)."],
                    ],
                    [
                        'type' => 'image',
                        'title' => 'صفحة صورة',
                        'content' => ['image_url' => $img, 'caption' => 'صورة توضيحية مع تعليق أسفلها.'],
                    ],
                    [
                        'type' => 'question',
                        'title' => 'صفحة سؤال',
                        'content' => ['question_id' => $q1, 'instructions' => 'اقرأ السؤال التالي وراجع الإجابة الصحيحة والشرح.'],
                    ],
                    [
                        'type' => 'mixed',
                        'title' => 'صفحة مختلطة',
                        'content' => ['sections' => [
                            ['type' => 'text', 'content' => "### قسم نصي\nشرح مبدئي قبل عرض الرسم التوضيحي."],
                            ['type' => 'image', 'content' => $img2],
                            ['type' => 'text', 'content' => "**ملاحظة:** الصورة أعلاه تلخّص الفكرة الأساسية."],
                        ]],
                    ],
                ],
            ],
            [
                'title' => '📗 درس نصي مكثّف',
                'description' => 'درس من عدة صفحات نصية متتابعة.',
                'color' => '#3B82F6',
                'pages' => [
                    ['type' => 'text', 'title' => 'المقدمة', 'content' => ['body' => "# المقدمة\nنبدأ بالأساسيات قبل الانتقال للتطبيق."]],
                    ['type' => 'text', 'title' => 'التفاصيل', 'content' => ['body' => "## التفاصيل\n1. خطوة أولى\n2. خطوة ثانية\n3. خطوة ثالثة"]],
                    ['type' => 'question', 'title' => 'تطبيق', 'content' => ['question_id' => $q2, 'instructions' => 'طبّق ما تعلمته.']],
                ],
            ],
            [
                'title' => '📙 درس بالصور',
                'description' => 'درس يعتمد على الوسائط البصرية.',
                'color' => '#F59E0B',
                'pages' => [
                    ['type' => 'image', 'title' => 'الشكل الأول', 'content' => ['image_url' => $img, 'caption' => 'الشكل الأول.']],
                    ['type' => 'image', 'title' => 'الشكل الثاني', 'content' => ['image_url' => $img2, 'caption' => 'الشكل الثاني.']],
                ],
            ],
        ];

        $order = Lesson::where('exam_id', $examId)->max('order') ?? 0;

        foreach ($lessons as $data) {
            $order++;
            $lesson = Lesson::updateOrCreate(
                ['exam_id' => $examId, 'title' => $data['title']],
                [
                    'description' => $data['description'],
                    'color' => $data['color'],
                    'order' => $order,
                    'duration_minutes' => 10 + count($data['pages']) * 3,
                    'is_active' => true,
                ]
            );

            // Rebuild pages from scratch for a clean, predictable demo.
            $lesson->pages()->delete();
            foreach ($data['pages'] as $i => $page) {
                LessonPage::create([
                    'lesson_id' => $lesson->id,
                    'page_number' => $i + 1,
                    'type' => $page['type'],
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'is_required' => true,
                ]);
            }
        }

        // Regenerate the demo student's plan so the new lessons appear scheduled.
        $student = Student::whereNotNull('exam_id')->where('exam_id', $examId)->first();
        if ($student && $student->exam_date) {
            DB::table('student_lesson_progress')->where('student_id', $student->id)->delete();
        }

        $this->command?->info('Seeded ' . count($lessons) . ' showcase lessons (every page type) for exam ' . $examId . '.');
    }
}
