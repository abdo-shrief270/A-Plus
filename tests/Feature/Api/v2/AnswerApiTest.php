<?php

namespace Tests\Feature\Api\v2;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Student;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AnswerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateStudent(): array
    {
        $user = User::factory()->create(['type' => 'student']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        
        $device = Device::create([
            'user_id' => $user->id,
            'device_id' => 'test-device-id',
            'device_type' => 'ios',
            'device_name' => 'Test iPhone',
            'fcm_token' => 'dummy_fcm',
            'is_approved' => true,
        ]);

        $token = JWTAuth::fromUser($user);
        return [$user, $student, $token];
    }

    public function test_student_can_submit_correct_answer()
    {
        [$user, $student, $token] = $this->authenticateStudent();
        
        $question = Question::factory()->create(['difficulty' => 'easy']);
        $correctAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
        
        // Initial score should be 0 or null
        $this->assertEquals(0, $student->current_score);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson(route('api.v2.questions.answer'), [
                             'question_id' => $question->id,
                             'answer_id' => $correctAnswer->id,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.is_correct', true)
                 ->assertJsonPath('data.score_earned', 10);

        // Assert score incremented (easy = 10 points)
        $this->assertEquals(10, $student->refresh()->current_score);
        
        // Assert student_answers table has the record
        $this->assertDatabaseHas('student_answers', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'answer_id' => $correctAnswer->id,
            'is_correct' => true,
            'score_earned' => 10,
        ]);
    }

    public function test_student_gets_correct_answer_when_submitting_wrong_answer()
    {
        [$user, $student, $token] = $this->authenticateStudent();
        
        $question = Question::factory()->create(['difficulty' => 'hard']);
        $correctAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
        $wrongAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson(route('api.v2.questions.answer'), [
                             'question_id' => $question->id,
                             'answer_id' => $wrongAnswer->id,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.is_correct', false)
                 ->assertJsonPath('data.score_earned', 0)
                 ->assertJsonPath('data.correct_answer', $correctAnswer->id);
    }

    public function test_student_cannot_farm_points_for_same_question()
    {
        [$user, $student, $token] = $this->authenticateStudent();
        
        $question = Question::factory()->create(['difficulty' => 'medium']);
        $correctAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        // First answer is correct
        $this->withHeader('Authorization', "Bearer $token")
             ->postJson(route('api.v2.questions.answer'), [
                 'question_id' => $question->id,
                 'answer_id' => $correctAnswer->id,
             ]);

        // 15 points for medium
        $this->assertEquals(15, $student->refresh()->current_score);

        // Submit the same correct answer again
        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson(route('api.v2.questions.answer'), [
                             'question_id' => $question->id,
                             'answer_id' => $correctAnswer->id,
                         ]);

        // Should still return success and is_correct but no additional score
        $response->assertStatus(200);

        // Score should still be 15
        $this->assertEquals(15, $student->refresh()->current_score);
    }
}
