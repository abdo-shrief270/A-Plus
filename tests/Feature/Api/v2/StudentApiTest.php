<?php

namespace Tests\Feature\Api\v2;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\League;
use App\Models\Student;
use App\Models\StudentDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser(string $type = 'student'): array
    {
        $user = User::factory()->create(['type' => $type]);
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    // =====================================================
    // Stats Endpoint Tests
    // =====================================================

    /** @test */
    public function it_returns_platform_stats_for_authenticated_user()
    {
        [$user, $token] = $this->authenticatedUser();
        Student::factory()->count(5)->create();
        Course::factory()->count(3)->create(['active' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/stats');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'total_students',
                    'total_courses',
                    'average_progress',
                    'new_students_last_month',
                    'active_enrollments',
                ],
            ]);
    }

    /** @test */
    public function stats_requires_authentication()
    {
        $response = $this->getJson('/api/v2/stats');

        $response->assertStatus(401);
    }

    // =====================================================
    // Trending Courses Tests
    // =====================================================

    /** @test */
    public function it_returns_trending_courses_ordered_by_enrollment()
    {
        [$user, $token] = $this->authenticatedUser();

        $popularCourse = Course::factory()->create(['active' => true, 'title' => 'Popular Course']);
        $normalCourse = Course::factory()->create(['active' => true, 'title' => 'Normal Course']);

        // Add more enrollments to popular course
        Enrollment::factory()->count(10)->create(['course_id' => $popularCourse->id]);
        Enrollment::factory()->count(2)->create(['course_id' => $normalCourse->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/trending-courses');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.0.title', 'Popular Course');
    }

    /** @test */
    public function it_respects_limit_parameter_for_trending_courses()
    {
        [$user, $token] = $this->authenticatedUser();
        Course::factory()->count(5)->create(['active' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/trending-courses?limit=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // =====================================================
    // Student Stats Tests
    // =====================================================

    /** @test */
    public function it_returns_student_stats_for_month_period()
    {
        [$user, $token] = $this->authenticatedUser();
        Student::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/student-stats?period=month');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'labels',
                    'datasets',
                    'summary',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_invalid_period_parameter()
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/student-stats?period=invalid');

        $response->assertStatus(200)
            ->assertJsonPath('status', 400);
    }

    // =====================================================
    // Student Management Tests
    // =====================================================

    /** @test */
    public function it_lists_students_ordered_by_newest()
    {
        [$user, $token] = $this->authenticatedUser('student');
        Student::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v2/students');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'user_name', 'league', 'total_score', 'total_points', 'joined_at'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_shows_student_with_details()
    {
        [$user, $token] = $this->authenticatedUser();
        $league = League::factory()->create();
        $student = Student::factory()->create(['current_league_id' => $league->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v2/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.id', $student->id);
    }

    /** @test */
    public function it_updates_student_profile()
    {
        [$user, $token] = $this->authenticatedUser();
        $student = Student::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/v2/students/{$student->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_creates_deletion_request_instead_of_deleting()
    {
        [$user, $token] = $this->authenticatedUser();
        $student = Student::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/v2/students/{$student->id}", [
                'reason' => 'Student graduated',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('student_deletion_requests', [
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        // Student should still exist
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    // =====================================================
    // Bulk Import Tests
    // =====================================================

    /** @test */
    public function it_creates_single_student()
    {
        [$user, $token] = $this->authenticatedUser('student');
        $exam = Exam::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v2/students', [
                'name' => 'New Student',
                'user_name' => 'newstudent123',
                'email' => 'new@student.com',
                'phone' => '1234567890',
                'gender' => 'male',
                'exam_id' => $exam->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 200);

        $this->assertDatabaseHas('users', ['user_name' => 'newstudent123']);
    }

    /** @test */
    public function it_creates_multiple_students_from_json_array()
    {
        [$user, $token] = $this->authenticatedUser('student');

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v2/students/bulk', [
                'students' => [
                    ['name' => 'Student One', 'user_name' => 'studentone'],
                    ['name' => 'Student Two', 'user_name' => 'studenttwo'],
                    ['name' => 'Student Three', 'user_name' => 'studentthree'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_created', 3)
            ->assertJsonPath('data.total_failed', 0);
    }

    /** @test */
    public function it_returns_failures_for_invalid_rows()
    {
        [$user, $token] = $this->authenticatedUser('student');

        // Create existing user to cause duplicate error
        User::factory()->create(['user_name' => 'existinguser']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v2/students/bulk', [
                'students' => [
                    ['name' => 'Valid Student', 'user_name' => 'validstudent'],
                    ['name' => 'Duplicate', 'user_name' => 'existinguser'], // Will fail
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_created', 1)
            ->assertJsonPath('data.total_failed', 1);
    }

    /** @test */
    public function it_imports_students_from_csv_file()
    {
        [$user, $token] = $this->authenticatedUser('student');

        Storage::fake('local');

        $csvContent = "name,user_name,email\nCSV Student 1,csvstudent1,csv1@test.com\nCSV Student 2,csvstudent2,csv2@test.com";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v2/students/import', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_bulk_request_structure()
    {
        [$user, $token] = $this->authenticatedUser('student');

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v2/students/bulk', [
                'students' => [], // Empty array
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 422); // Validation error
    }
}
