<?php

namespace Tests\Feature\Quiz;

use App\Models\QuizSession;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

/**
 * HTTP-layer guarantees: the reveal gate (no correctness leak before
 * finalization) and per-student authorization. Device/JWT middleware is
 * stripped so we exercise the controller's own auth checks.
 */
class QuizApiTest extends TestCase
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
    }

    private function actingAsStudent($student): static
    {
        $this->actingAs($student->user, 'api');

        return $this;
    }

    public function test_exam_mode_in_progress_hides_correctness_and_explanation(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 3);
        $session = app(QuizService::class)->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);

        $res = $this->actingAsStudent($student)->getJson("/api/v2/quizzes/{$session->id}");

        $res->assertOk();
        $q = $res->json('data.session.questions.0');
        $this->assertArrayNotHasKey('is_correct', $q, 'exam in-progress must not leak correctness');
        $this->assertArrayNotHasKey('correct_answer_id', $q);
        $this->assertArrayNotHasKey('explanation', $q);
        // Answer options themselves must never carry is_correct.
        $this->assertArrayNotHasKey('is_correct', $q['question']['answers'][0]);
    }

    public function test_completed_session_reveals_correctness(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 3);
        $service = app(QuizService::class);
        $session = $service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);
        $service->completeSession($session);

        $res = $this->actingAsStudent($student)->getJson("/api/v2/quizzes/{$session->id}");

        $q = $res->json('data.session.questions.0');
        $this->assertArrayHasKey('is_correct', $q, 'completed session must reveal correctness');
        $this->assertArrayHasKey('correct_answer_id', $q);
        $this->assertArrayHasKey('explanation', $q);
    }

    public function test_tutor_mode_reveals_only_after_answering(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);
        $session = app(QuizService::class)->createSession($student, [
            'mode' => 'tutor', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);

        // Before answering: hidden.
        $before = $this->actingAsStudent($student)->getJson("/api/v2/quizzes/{$session->id}");
        $unanswered = collect($before->json('data.session.questions'))->firstWhere('answered_at', null);
        $this->assertArrayNotHasKey('correct_answer_id', $unanswered);

        // Answer one, then it reveals for that row.
        $first = $session->questions()->orderBy('order')->first();
        $q = $questions->firstWhere('id', $first->question_id);
        $answer = $this->actingAsStudent($student)->postJson("/api/v2/quizzes/{$session->id}/answer", [
            'question_id' => $q->id,
            'answer_id' => $this->correctAnswerId($q),
        ]);
        $answer->assertOk();
        $this->assertTrue($answer->json('data.is_correct'));
        $this->assertArrayHasKey('explanation', $answer->json('data'));
    }

    public function test_cannot_access_another_students_session(): void
    {
        $owner = $this->makeStudent();
        $category = $this->makeCategory($owner->exam);
        $this->makeQuestions($category, 3);
        $session = app(QuizService::class)->createSession($owner, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);

        $intruder = $this->makeStudent();

        $this->actingAsStudent($intruder)
            ->getJson("/api/v2/quizzes/{$session->id}")
            ->assertNotFound();

        $this->actingAsStudent($intruder)
            ->postJson("/api/v2/quizzes/{$session->id}/complete")
            ->assertNotFound();
    }

    public function test_duplicate_create_returns_conflict_with_active_id(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 5);
        $payload = [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 2,
        ];

        $first = $this->actingAsStudent($student)->postJson('/api/v2/quizzes', $payload);
        $first->assertCreated();

        $second = $this->actingAsStudent($student)->postJson('/api/v2/quizzes', $payload);
        $second->assertStatus(409);
        $this->assertSame($first->json('data.session.id'), $second->json('data.active_session_id'));
    }

    public function test_validation_rejects_bad_config(): void
    {
        $student = $this->makeStudent();

        $res = $this->actingAsStudent($student)->postJson('/api/v2/quizzes', [
            'mode' => 'hack',
            'source' => 'everything',
            'question_count' => -3,
        ]);

        // Validation failures render as 422 in the body.
        $this->assertSame(422, $res->json('status'));
        $this->assertArrayHasKey('mode', $res->json('errors'));
        $this->assertArrayHasKey('source', $res->json('errors'));
        $this->assertArrayHasKey('question_count', $res->json('errors'));
    }
}
