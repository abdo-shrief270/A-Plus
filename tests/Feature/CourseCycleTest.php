<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CourseCycleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_enroll_in_course_with_coupon()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'price' => 100.00,
            'active' => true,
            'level' => 'beginner',
        ]);

        $coupon = Coupon::create([
            'code' => 'TEST50',
            'type' => 'percentage',
            'value' => 50, // 50% off
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        // 2. Attempt Enrollment via API
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/v1/courses/{$course->id}/enroll", [
                'coupon_code' => 'TEST50',
            ]);

        // 3. Assert Response Logic (Pending Payment)
        $response->assertStatus(200)
            ->assertJsonPath('data.amount', 50) // 100 - 50%
            ->assertJsonPath('data.coupon_applied', 'TEST50');

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'pending',
        ]);

        // 4. Simulate Payment
        $enrollmentId = $response->json('data.enrollment_id');

        $paymentResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/v1/payment/initiate", [
                'enrollment_id' => $enrollmentId,
                'payment_method' => 'visa',
            ]);

        $paymentResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['redirect_url']]);

        $this->assertDatabaseHas('payments', [
            'enrollment_id' => $enrollmentId,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_enroll_in_free_course()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $course = Course::create([
            'title' => 'Free Course',
            'slug' => 'free-course',
            'price' => 0.00,
            'active' => true,
            'level' => 'beginner',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/v1/courses/{$course->id}/enroll");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active'); // Auto-active

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }
}
