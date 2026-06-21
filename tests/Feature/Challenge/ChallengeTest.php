<?php

namespace Tests\Feature\Challenge;

use App\Models\Challenge;
use App\Models\QuizSession;
use App\Services\ChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private ChallengeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChallengeService::class);
    }

    public function test_create_freezes_questions_and_starts_creator_session(): void
    {
        $creator = $this->makeStudent();
        $category = $this->makeCategory($creator->exam);
        $this->makeQuestions($category, 5);

        $result = $this->service->create($creator, [
            'mode' => 'exam', 'source' => 'random',
            'category_ids' => [$category->id], 'question_count' => 4,
        ]);

        $this->assertNotEmpty($result['challenge']->invite_code);
        $this->assertSame(4, $result['challenge']->question_count);
        $this->assertSame($result['challenge']->id, $result['session']->challenge_id);
        $this->assertSame(4, $result['session']->questions()->count());
    }

    public function test_joiner_gets_the_identical_frozen_set(): void
    {
        $creator = $this->makeStudent();
        $category = $this->makeCategory($creator->exam);
        $this->makeQuestions($category, 6);
        $created = $this->service->create($creator, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 4,
        ]);

        $joiner = $this->makeStudent($creator->exam);
        $joined = $this->service->join($joiner, $created['challenge']->invite_code);

        $creatorQ = $created['session']->questions()->pluck('question_id')->sort()->values()->all();
        $joinerQ = $joined['session']->questions()->pluck('question_id')->sort()->values()->all();
        $this->assertSame($creatorQ, $joinerQ, 'both players must answer the same frozen set');
    }

    public function test_join_with_bad_code_throws(): void
    {
        $student = $this->makeStudent();
        $this->expectException(ValidationException::class);
        $this->service->join($student, 'NOPE12');
    }

    public function test_rejoin_returns_existing_session(): void
    {
        $creator = $this->makeStudent();
        $category = $this->makeCategory($creator->exam);
        $this->makeQuestions($category, 5);
        $created = $this->service->create($creator, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);
        $joiner = $this->makeStudent($creator->exam);

        $first = $this->service->join($joiner, $created['challenge']->invite_code);
        $second = $this->service->join($joiner, $created['challenge']->invite_code);

        $this->assertSame($first['session']->id, $second['session']->id);
    }

    public function test_results_rank_finished_above_in_progress(): void
    {
        $creator = $this->makeStudent();
        $category = $this->makeCategory($creator->exam);
        $this->makeQuestions($category, 5);
        $created = $this->service->create($creator, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);
        $challenge = $created['challenge'];

        // Creator finishes with a score; joiner stays in progress.
        $created['session']->update(['status' => QuizSession::STATUS_COMPLETED, 'score_percent' => 66.67, 'completed_at' => now()]);
        $joiner = $this->makeStudent($creator->exam);
        $this->service->join($joiner, $challenge->invite_code);

        $results = $this->service->results($challenge->fresh());
        $this->assertCount(2, $results['participants']);
        // Finished participant ranks first; in-progress has null score.
        $this->assertSame(66.67, $results['participants'][0]['score_percent']);
        $this->assertNull($results['participants'][1]['score_percent']);
        $this->assertTrue($results['participants'][0]['is_creator']);
    }
}
