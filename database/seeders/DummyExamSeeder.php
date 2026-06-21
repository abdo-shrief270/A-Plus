<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\SectionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dummy academic structure for development/demo: builds a realistic
 * exam → section → category tree and links random subsets of the existing
 * question bank to every category, so question-bank browsing, quizzes,
 * and the daily challenge work on every exam.
 *
 * Idempotent: exams/sections/categories are upserted by name, and questions
 * are only attached to categories that have no links yet.
 */
class DummyExamSeeder extends Seeder
{
    /** Questions linked per category (clamped to the available pool). */
    private const MIN_PER_CATEGORY = 80;
    private const MAX_PER_CATEGORY = 200;

    public function run(): void
    {
        $structure = [
            'قدرات عامة' => [
                'كمي' => ['حسابي', 'هندسي', 'جبري', 'إحصائي', 'مقارنات'],
                'لفظي' => ['استيعاب المقروء', 'التناظر اللفظي', 'إكمال الجمل', 'الخطأ السياقي'],
            ],
            'تحصيلي علمي' => [
                'رياضيات' => ['التفاضل والتكامل', 'الدوال', 'المتجهات', 'الاحتمالات'],
                'فيزياء' => ['الحركة', 'الكهرباء', 'الموجات'],
                'كيمياء' => ['التفاعلات الكيميائية', 'الجدول الدوري'],
                'أحياء' => ['الخلية', 'الوراثة'],
            ],
            'الصف الثالث الثانوي' => [
                'الفصل الأول' => ['الوحدة الأولى', 'الوحدة الثانية', 'الوحدة الثالثة'],
                'الفصل الثاني' => ['الوحدة الرابعة', 'الوحدة الخامسة'],
            ],
        ];

        $allQuestionIds = Question::pluck('id');
        if ($allQuestionIds->isEmpty()) {
            $this->command?->warn('No questions in the bank — nothing to link.');

            return;
        }

        foreach ($structure as $examName => $sections) {
            $exam = Exam::updateOrCreate(['name' => $examName], ['active' => true]);

            foreach ($sections as $sectionName => $categories) {
                $section = ExamSection::updateOrCreate(
                    ['exam_id' => $exam->id, 'name' => $sectionName]
                );

                foreach ($categories as $categoryName) {
                    $category = SectionCategory::updateOrCreate(
                        ['exam_section_id' => $section->id, 'name' => $categoryName],
                        ['description' => "أسئلة تصنيف {$categoryName} (بيانات تجريبية)"]
                    );

                    $this->linkQuestions($category, $allQuestionIds);
                }
            }
        }

        $this->command?->info('Dummy exams seeded: ' . Exam::count() . ' exams, '
            . ExamSection::count() . ' sections, ' . SectionCategory::count() . ' categories.');
    }

    private function linkQuestions(SectionCategory $category, $allQuestionIds): void
    {
        $alreadyLinked = DB::table('category_questions')
            ->where('section_category_id', $category->id)
            ->exists();
        if ($alreadyLinked) {
            return;
        }

        $count = min(
            random_int(self::MIN_PER_CATEGORY, self::MAX_PER_CATEGORY),
            $allQuestionIds->count()
        );

        $now = now();
        $rows = $allQuestionIds->random($count)->map(fn ($qid) => [
            'section_category_id' => $category->id,
            'question_id' => $qid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('category_questions')->insert($chunk);
        }
    }
}
