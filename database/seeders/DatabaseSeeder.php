<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Answer;
use App\Models\Bookmark;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Parentt;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\School;
use App\Models\SectionCategory;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\StudentSchool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // // 1. Roles & Permissions (Shield)
        // Artisan::call('shield:install admin');
        // Artisan::call('shield:generate --all --panel=admin');
        // $this->command->info("Shield Installed.");

        // $superAdminRole = Role::firstOrCreate(['name' => 'مدير النظام', 'guard_name' => 'web']);
        // Role::firstOrCreate(['name' => 'المدير', 'guard_name' => 'web']);
        // Role::firstOrCreate(['name' => 'مدخل بيانات', 'guard_name' => 'web']);
        // Role::firstOrCreate(['name' => 'مبيعات', 'guard_name' => 'web']);

        // $permissions = Permission::all();
        // if ($superAdminRole) {
        //     $superAdminRole->syncPermissions($permissions);
        // }

        // // 2. Admins
        // Admin::firstOrCreate(
        //     ['email' => 'abdo.shrief270@gmail.com'],
        //     [
        //         'name' => 'Abdo Shrief',
        //         'password' => Hash::make('954816899'),
        //         'active' => true
        //     ]
        // );
        // Artisan::call('shield:super-admin --user=1 --panel=admin');
        // $this->command->info("Admin Created.");

        // // 3. Question Types
        // $this->call(QuestionTypeSeeder::class);
        // $types = QuestionType::all()->keyBy('name');

        // // 4. Schools (3 Schools)
        // $schools = School::factory()->count(3)->create();
        // $this->command->info("Schools Created.");

        // // 5. Contacts (5 Inquiries)
        // Contact::factory()->count(5)->create();
        // $this->command->info("Contacts Created.");

        // // 6. Exams Structure (2 Exams)
        // $exams = Exam::factory()->count(2)->create();

        // foreach ($exams as $exam) {
        //     // Subjects (2 per exam)
        //     $subjects = ExamSubject::factory()->count(2)->create(['exam_id' => $exam->id]);

        //     // Sections & Categories (2 Sections -> 2 Categories each)
        //     $sections = ExamSection::factory()->count(2)->create(['exam_id' => $exam->id]);
        //     $categories = collect();
        //     foreach ($sections as $section) {
        //         $categories = $categories->merge(
        //             SectionCategory::factory()->count(2)->create(['exam_section_id' => $section->id])
        //         );
        //     }

        //     // Questions (20 per Exam)
        //     $this->command->info("Seeding Questions for: " . $exam->name);

        //     $questions = Question::factory()->count(20)->make()->each(function ($question) use ($types) {
        //         $type = $types->random();
        //         $question->question_type_id = $type->id;
        //         $question->save();

        //         if ($type->name === 'مقارنة') {
        //             $defAnswers = $type->def_answers ? json_decode($type->def_answers, true) : [];
        //             if ($defAnswers) {
        //                 foreach ($defAnswers as $def) {
        //                     Answer::create([
        //                         'question_id' => $question->id,
        //                         'text' => $def['text'],
        //                         'order' => $def['order'],
        //                         'is_correct' => $def['order'] === 1,
        //                     ]);
        //                 }
        //             }
        //         } else {
        //             Answer::factory()->create([
        //                 'question_id' => $question->id,
        //                 'is_correct' => true,
        //                 'order' => 1
        //             ]);
        //             Answer::factory()->count(3)
        //                 ->sequence(fn($sequence) => ['order' => $sequence->index + 2])
        //                 ->create([
        //                     'question_id' => $question->id,
        //                     'is_correct' => false,
        //                 ]);
        //         }
        //     });

        //     // Link Questions
        //     Question::whereIn('id', $questions->pluck('id'))->each(function ($q) use ($subjects, $categories) {
        //         if ($subjects->isNotEmpty())
        //             $q->subjects()->attach($subjects->random()->id);
        //         if ($categories->isNotEmpty())
        //             $q->categories()->attach($categories->random()->id);
        //     });
        // }

        // // 7. Parents (5 Parents)
        // $parents = User::factory()->count(5)->create([
        //     'type' => 'parent',
        //     'user_name' => fn() => fake()->unique()->userName(),
        //     'password' => Hash::make('password'),
        // ]);
        // $this->command->info("Parents Created.");

        // // 8. Students (10 Students)
        // $studentUsers = User::factory()->count(10)->create([
        //     'type' => 'student',
        //     'user_name' => fn() => fake()->unique()->userName(),
        //     'password' => Hash::make('password'),
        // ]);

        // foreach ($studentUsers as $user) {
        //     $student = Student::factory()->create([
        //         'user_id' => $user->id,
        //         'exam_id' => $exams->random()->id,
        //     ]);

        //     StudentSchool::create([
        //         'student_id' => $student->id,
        //         'school_id' => $schools->random()->id,
        //     ]);

        //     // Actions: Bookmarks
        //     Bookmark::factory()->count(rand(1, 3))->create([
        //         'student_id' => $student->id,
        //         'question_id' => Question::inRandomOrder()->first()->id
        //     ]);

        //     // Actions: Answers (Simulate taking a quiz)
        //     // Pick rand 5-10 questions
        //     $quizQuestions = Question::inRandomOrder()->take(rand(5, 10))->get();
        //     foreach ($quizQuestions as $q) {
        //         // Pick a random answer from this question
        //         $answer = $q->answers()->inRandomOrder()->first();
        //         if ($answer) {
        //             StudentAnswer::create([
        //                 'student_id' => $student->id,
        //                 'question_id' => $q->id,
        //                 'answer_id' => $answer->id,
        //                 'is_correct' => $answer->is_correct,
        //             ]);
        //         }
        //     }
        // }
        // $this->command->info("Students & Actions Created.");

        // // 11. Lessons & Pages
        // $this->call(LessonSeeder::class);
        $this->call(SuperAdminSeeder::class);
        $this->call(QuestionTypeSeeder::class);
//        $this->call(DummyDataSeeder::class);
//        $this->call(LessonSeeder::class);
    }
}
