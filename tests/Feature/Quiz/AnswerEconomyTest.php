<?php

namespace Tests\Feature\Quiz;

use App\Models\Plan;
use App\Models\StudentAnswer;
use App\Models\StudentScore;
use App\Models\Subscription;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

/**
 * The question-bank answer cycle: revision visibility (student_id), league
 * points, and the wallet economy (flat charge, pay-once, subscriber bypass).
 */
class AnswerEconomyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\JwtMiddleware::class,
            \App\Http\Middleware\EnforceSingleDevice::class,
        ]);
        config(['learning.question_answer_cost' => 1]);
    }

    private function answer($student, $question, $answerId)
    {
        $this->actingAs($student->user, 'api');

        return $this->postJson('/api/v2/questions/answer', [
            'question_id' => $question->id,
            'answer_id' => $answerId,
        ]);
    }

    public function test_answer_is_recorded_with_student_id_for_revision(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 100]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        $this->answer($student, $question, $this->correctAnswerId($question))->assertOk();

        // student_id MUST be populated — this is what the revision page reads.
        $this->assertDatabaseHas('student_answers', [
            'student_id' => $student->id,
            'user_id' => $student->user_id,
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
    }

    public function test_correct_answer_awards_league_points_once(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 100]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        $this->answer($student, $question, $this->correctAnswerId($question))->assertOk();
        $this->answer($student, $question, $this->correctAnswerId($question))->assertOk();

        // League points awarded exactly once despite two correct submissions.
        $this->assertSame(1, StudentScore::where('student_id', $student->id)->where('reason', 'question_correct')->count());
    }

    public function test_first_answer_charges_flat_cost_and_reanswer_is_free(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 5]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        $first = $this->answer($student, $question, $this->wrongAnswerId($question));
        $this->assertSame(4, $first->json('data.balance'), 'first answer charges 1');

        $second = $this->answer($student, $question, $this->correctAnswerId($question));
        $this->assertSame(4, $second->json('data.balance'), 're-answer is free (pay-once)');
    }

    public function test_insufficient_balance_rejects_and_does_not_record(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 0]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        $res = $this->answer($student, $question, $this->correctAnswerId($question));

        $this->assertSame(402, $res->json('status'));
        $this->assertDatabaseMissing('student_answers', [
            'student_id' => $student->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_active_subscriber_answers_free(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 0]);
        $plan = Plan::create([
            'name' => 'شهري', 'type' => 'subscription', 'price' => 50,
            'points' => 0, 'duration_days' => 30, 'is_active' => true,
        ]);
        Subscription::create([
            'student_id' => $student->id, 'plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30),
        ]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        // Zero balance, but an active subscription → answers free.
        $this->answer($student, $question, $this->correctAnswerId($question))->assertOk();
        $this->assertDatabaseHas('student_answers', [
            'student_id' => $student->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_pack_subscription_does_not_grant_unlimited(): void
    {
        $student = $this->makeStudent();
        Wallet::create(['student_id' => $student->id, 'balance' => 0]);
        $plan = Plan::create([
            'name' => 'باقة نقاط', 'type' => 'pack', 'price' => 20,
            'points' => 100, 'duration_days' => null, 'is_active' => true,
        ]);
        Subscription::create([
            'student_id' => $student->id, 'plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => null,
        ]);
        $category = $this->makeCategory($student->exam);
        $question = $this->makeQuestions($category, 1)->first();

        // A pack is a point top-up, not unlimited access → still charged → 402 at 0 balance.
        $this->answer($student, $question, $this->correctAnswerId($question))->assertStatus(402);
    }
}
