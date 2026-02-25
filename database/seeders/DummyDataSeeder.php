<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $password = Hash::make('password123');

        // ==========================================
        // 1. Exams (2 Exams)
        // ==========================================
        $examQudratId = DB::table('exams')->insertGetId([
            'name' => 'اختبار القدرات العامة',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $examTahsiliId = DB::table('exams')->insertGetId([
            'name' => 'الاختبار التحصيلي',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 2. Exam Subjects (3 Subjects)
        // ==========================================
        $subjectMathId = DB::table('exam_subjects')->insertGetId([
            'exam_id' => $examQudratId,
            'name' => 'الرياضيات',
            'description' => 'أسئلة كمية تشمل الجبر والهندسة والإحصاء.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectArabicId = DB::table('exam_subjects')->insertGetId([
            'exam_id' => $examQudratId,
            'name' => 'اللغة العربية',
            'description' => 'أسئلة لفظية تشمل التناظر اللفظي واستيعاب المقروء.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectScienceId = DB::table('exam_subjects')->insertGetId([
            'exam_id' => $examTahsiliId,
            'name' => 'العلوم الطبيعية',
            'description' => 'أسئلة علمية تشمل الفيزياء والكيمياء والأحياء.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 3. Exam Sections (10 Sections)
        // ==========================================
        $sections = [];
        for ($i = 1; $i <= 5; $i++) {
            $sections[] = DB::table('exam_sections')->insertGetId([
                'exam_id' => $examQudratId,
                'name' => 'القسم الكمي واللفظي ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            $sections[] = DB::table('exam_sections')->insertGetId([
                'exam_id' => $examTahsiliId,
                'name' => 'القسم العلمي ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 4. Section Categories (4 Categories)
        // ==========================================
        $categoryAlgebraId = DB::table('section_categories')->insertGetId([
            'exam_section_id' => $sections[0],
            'name' => 'الجبر والحساب',
            'description' => 'مسائل جبرية وحسابية متنوعة.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryGeometryId = DB::table('section_categories')->insertGetId([
            'exam_section_id' => $sections[0],
            'name' => 'الهندسة',
            'description' => 'مسائل الزوايا والمساحات.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryReadingId = DB::table('section_categories')->insertGetId([
            'exam_section_id' => $sections[1],
            'name' => 'استيعاب المقروء',
            'description' => 'قراءة نصوص والإجابة على أسئلة متعلقة بها.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryPhysicsId = DB::table('section_categories')->insertGetId([
            'exam_section_id' => $sections[5],
            'name' => 'الفيزياء الكلاسيكية',
            'description' => 'قوانين نيوتن والحركة.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 5. Parent and School
        // ==========================================
        $parentId = DB::table('users')->insertGetId([
            'name' => 'أبو محمد',
            'user_name' => 'abu_mohamed',
            'phone' => '0500000001',
            'email' => 'parent@example.com',
            'password' => $password,
            'type' => 'parent',
            'gender' => 'male',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'مدرسة الأندلس الأهلية',
            'user_name' => 'alandalus_school',
            'password' => $password,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 6. Students (10 Linked, 1 Unlinked)
        // ==========================================
        $studentNames = [
            'محمد عبدالله', 'أحمد فهد', 'سعود عبدالعزيز', 'خالد سلمان', 'فيصل عبدالرحمن', 
            'نورة محمد', 'سارة سعد', 'لمى طارق', 'ريما فهد', 'شهد ناصر'
        ];

        foreach ($studentNames as $index => $name) {
            $userId = DB::table('users')->insertGetId([
                'name' => $name,
                'user_name' => 'student_' . $index,
                'phone' => '05500000' . sprintf('%02d', $index),
                'email' => 'student' . $index . '@example.com',
                'password' => $password,
                'type' => 'student',
                'gender' => ($index < 5) ? 'male' : 'female',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id' => $userId,
                'exam_id' => ($index % 2 == 0) ? $examQudratId : $examTahsiliId,
                'id_number' => '100000000' . $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link to Parent
            DB::table('student_parents')->insert([
                'parent_id' => $parentId,
                'student_id' => $studentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link to School
            DB::table('student_schools')->insert([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 1 Unlinked Student
        $unlinkedUserId = DB::table('users')->insertGetId([
            'name' => 'طالب مستقل',
            'user_name' => 'independent_student',
            'phone' => '0599999999',
            'email' => 'indep@example.com',
            'password' => $password,
            'type' => 'student',
            'gender' => 'male',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('students')->insert([
            'user_id' => $unlinkedUserId,
            'exam_id' => $examQudratId,
            'id_number' => '2000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 7. Question Types
        // ==========================================
        $typeMultipleChoice = DB::table('question_types')->insertGetId([
            'name' => 'خيارات متعددة',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $typeTrueFalse = DB::table('question_types')->insertGetId([
            'name' => 'صح أم خطأ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // 8. Questions and Answers
        // ==========================================
        $questionsData = [
            [
                'text' => 'إذا كان ثمن قلم وكتاب 72 ريالاً، وثمن الكتاب يساوي ثلاثة أضعاف ثمن القلم، فكم ثمن القلم؟',
                'difficulty' => 'easy',
                'subject_id' => $subjectMathId,
                'category_id' => $categoryAlgebraId,
                'answers' => [
                    ['text' => '18', 'is_correct' => 1],
                    ['text' => '24', 'is_correct' => 0],
                    ['text' => '12', 'is_correct' => 0],
                    ['text' => '36', 'is_correct' => 0],
                ]
            ],
            [
                'text' => 'ما العلاقة في (طبيب : مستشفى)؟',
                'difficulty' => 'medium',
                'subject_id' => $subjectArabicId,
                'category_id' => $categoryReadingId,
                'answers' => [
                    ['text' => 'معلم : مدرسة', 'is_correct' => 1],
                    ['text' => 'سيارة : شارع', 'is_correct' => 0],
                    ['text' => 'نجار : خشب', 'is_correct' => 0],
                    ['text' => 'سماء : نجوم', 'is_correct' => 0],
                ]
            ],
            [
                'text' => 'وحدة قياس القوة في النظام الدولي هي:',
                'difficulty' => 'easy',
                'subject_id' => $subjectScienceId,
                'category_id' => $categoryPhysicsId,
                'answers' => [
                    ['text' => 'النيوتن', 'is_correct' => 1],
                    ['text' => 'الجول', 'is_correct' => 0],
                    ['text' => 'الواط', 'is_correct' => 0],
                    ['text' => 'الباسكال', 'is_correct' => 0],
                ]
            ],
            [
                'text' => 'مجموع قياسات الزوايا الداخلية للمثلث يساوي:',
                'difficulty' => 'easy',
                'subject_id' => $subjectMathId,
                'category_id' => $categoryGeometryId,
                'answers' => [
                    ['text' => '180 درجة', 'is_correct' => 1],
                    ['text' => '360 درجة', 'is_correct' => 0],
                    ['text' => '90 درجة', 'is_correct' => 0],
                    ['text' => '270 درجة', 'is_correct' => 0],
                ]
            ],
            [
                'text' => 'الجسم الساكن يبقى ساكناً ما لم تؤثر عليه قوة محصلة خارجية. تعبر هذه العبارة عن:',
                'difficulty' => 'medium',
                'subject_id' => $subjectScienceId,
                'category_id' => $categoryPhysicsId,
                'answers' => [
                    ['text' => 'قانون نيوتن الأول', 'is_correct' => 1],
                    ['text' => 'قانون نيوتن الثاني', 'is_correct' => 0],
                    ['text' => 'قانون نيوتن الثالث', 'is_correct' => 0],
                    ['text' => 'قانون الجذب العام', 'is_correct' => 0],
                ]
            ]
        ];

        foreach ($questionsData as $qData) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'text' => $qData['text'],
                'question_type_id' => $typeMultipleChoice,
                'difficulty' => $qData['difficulty'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('subject_questions')->insert([
                'exam_subject_id' => $qData['subject_id'],
                'question_id' => $questionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('category_questions')->insert([
                'section_category_id' => $qData['category_id'],
                'question_id' => $questionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($qData['answers'] as $index => $answer) {
                DB::table('answers')->insert([
                    'question_id' => $questionId,
                    'text' => $answer['text'],
                    'is_correct' => $answer['is_correct'],
                    'order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ==========================================
        // 9. Courses & Lessons (Make project not empty)
        // ==========================================
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'الدورة التأسيسية الشاملة لاختبار القدرات',
            'slug' => 'qudrat-comprehensive-course',
            'description' => 'دورة مكثفة تغطي جميع أقسام اختبار القدرات الكمي واللفظي بشكل كامل.',
            'price' => 199.00,
            'total_hours' => 30,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link Course to Exam
        DB::table('course_exam')->insert([
            'course_id' => $courseId,
            'exam_id' => $examQudratId,
        ]);

        // Add some Lessons
        $lessonNames = ['أساسيات الجبر', 'مهارات التناظر اللفظي', 'حلول الهندسة الزوايا'];
        foreach ($lessonNames as $index => $lessonName) {
            DB::table('lessons')->insert([
                'exam_id' => $examQudratId,
                'title' => $lessonName,
                'description' => 'شرح تفصيلي لـ ' . $lessonName,
                'order' => $index + 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
