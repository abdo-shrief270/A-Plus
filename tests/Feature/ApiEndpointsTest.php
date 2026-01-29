<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\SectionCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_register_a_student()
    {
        $exam = Exam::factory()->create(['active' => true]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'user_name' => 'johndoe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'country_code' => '+20',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'student',
            'gender' => 'male',
            'exam_id' => $exam->id,
            'exam_date' => now()->addMonth()->toDateString(),
            'id_number' => '1234567890',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'name',
                        'user_name',
                        'email',
                        'phone',
                        'type',
                        'gender',
                        'exam_id',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('students', ['id_number' => '1234567890']);
    }

    /** @test */
    public function it_can_login_a_user()
    {
        $user = User::factory()->create([
            'user_name' => 'johndoe',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'user_name' => 'johndoe',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'token',
                    'type',
                ],
            ]);
    }

    /** @test */
    public function it_returns_only_active_exams()
    {
        Exam::factory()->create(['name' => 'Active Exam', 'active' => true]);
        Exam::factory()->create(['name' => 'Inactive Exam', 'active' => false]);

        $response = $this->getJson('/api/v1/exams');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.exams')
            ->assertJsonPath('data.exams.0.name', 'Active Exam');
    }

    /** @test */
    public function it_can_fetch_student_profile()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.email', $user->email);
    }

    /** @test */
    public function it_can_update_student_profile()
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/user/update', [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    /** @test */
    public function it_can_fetch_exam_categories()
    {
        $user = User::factory()->create(['type' => 'student']);
        $exam = Exam::factory()->create(['active' => true]);
        Student::factory()->create(['user_id' => $user->id, 'exam_id' => $exam->id]);

        $section = ExamSection::factory()->create(['exam_id' => $exam->id]);
        SectionCategory::factory()->count(2)->create(['exam_section_id' => $section->id]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/exams/data');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['sections']]);
    }

    /** @test */
    public function it_can_fetch_subject_questions()
    {
        $subject = ExamSubject::factory()->create();
        $questions = Question::factory()->count(2)->create();
        $subject->questions()->attach($questions->pluck('id'));

        $response = $this->getJson("/api/v1/exams/subjects/{$subject->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.questions');
    }

    /** @test */
    public function it_can_update_lesson_progress()
    {
        $user = User::factory()->create(['type' => 'student']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $lesson = Lesson::factory()->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/v1/lessons/{$lesson->id}/progress", [
                'status' => 'completed',
                'progress_percentage' => 100,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.progress.status', 'completed');

        $this->assertDatabaseHas('student_lesson_progress', [
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_checks_username_availability()
    {
        User::factory()->create(['user_name' => 'taken_name']);

        $response = $this->postJson('/api/v1/auth/username/check', [
            'user_name' => 'taken_name',
        ]);
        $response->assertStatus(200)->assertJsonPath('data.available', false);

        $response = $this->postJson('/api/v1/auth/username/check', [
            'user_name' => 'available_name',
        ]);
        $response->assertStatus(200)->assertJsonPath('data.available', true);
    }
}
