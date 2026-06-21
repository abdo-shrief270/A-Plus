<?php

namespace Tests\Feature\Quiz;

use App\Models\Bookmark;
use App\Models\QuizSession;
use App\Models\StudentAnswer;
use App\Models\StudentScore;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private QuizService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuizService::class);
    }

    // ---- Pool source modes ----

    public function test_random_pool_returns_all_linked_questions(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 5);

        $count = $this->service->poolCount($student, [
            'source' => 'random',
            'category_ids' => [$category->id],
        ]);

        $this->assertSame(5, $count);
    }

    public function test_unanswered_pool_excludes_answered_questions(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 5);

        // Answer 2 of them globally.
        $questions->take(2)->each(fn ($q) => StudentAnswer::create([
            'user_id' => $student->user_id,
            'question_id' => $q->id,
            'is_correct' => true,
            'score_earned' => 10,
        ]));

        $count = $this->service->poolCount($student, [
            'source' => 'unanswered',
            'category_ids' => [$category->id],
        ]);

        $this->assertSame(3, $count);
    }

    public function test_wrong_pool_returns_only_incorrectly_answered(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 5);

        StudentAnswer::create(['user_id' => $student->user_id, 'question_id' => $questions[0]->id, 'is_correct' => false, 'score_earned' => 0]);
        StudentAnswer::create(['user_id' => $student->user_id, 'question_id' => $questions[1]->id, 'is_correct' => true, 'score_earned' => 10]);

        $count = $this->service->poolCount($student, [
            'source' => 'wrong',
            'category_ids' => [$category->id],
        ]);

        $this->assertSame(1, $count);
    }

    public function test_bookmarked_pool_returns_only_bookmarks(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 5);

        Bookmark::create(['student_id' => $student->id, 'question_id' => $questions[0]->id]);
        Bookmark::create(['student_id' => $student->id, 'question_id' => $questions[1]->id]);

        $count = $this->service->poolCount($student, [
            'source' => 'bookmarked',
            'category_ids' => [],
        ]);

        $this->assertSame(2, $count);
    }

    public function test_scope_drops_categories_outside_student_exam(): void
    {
        $student = $this->makeStudent();
        $ownCategory = $this->makeCategory($student->exam);
        $this->makeQuestions($ownCategory, 3);

        // A category in a DIFFERENT exam — must be ignored.
        $foreignCategory = $this->makeCategory();
        $this->makeQuestions($foreignCategory, 4);

        $count = $this->service->poolCount($student, [
            'source' => 'random',
            'category_ids' => [$ownCategory->id, $foreignCategory->id],
        ]);

        $this->assertSame(3, $count, 'Foreign-exam category must be silently dropped');
    }

    // ---- Session creation & guards ----

    public function test_create_session_freezes_questions_and_caps_count(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 3);

        $session = $this->service->createSession($student, [
            'mode' => 'exam',
            'source' => 'random',
            'category_ids' => [$category->id],
            'question_count' => 10, // more than available
        ]);

        $this->assertSame(3, $session->question_count, 'Count capped to pool size');
        $this->assertSame(3, $session->questions()->count());
        $this->assertEqualsCanonicalizing([1, 2, 3], $session->questions()->pluck('order')->all());
    }

    public function test_only_one_in_progress_session_per_student(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 5);

        $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 2,
        ]);

        $this->expectException(\App\Exceptions\QuizConflictException::class);
        $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 2,
        ]);
    }

    public function test_empty_pool_throws_validation(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam); // no questions

        $this->expectException(ValidationException::class);
        $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 5,
        ]);
    }

    public function test_expired_session_does_not_block_new_create(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 5);

        $first = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id],
            'question_count' => 2, 'time_limit_minutes' => 10,
        ]);
        // Force its deadline into the past.
        $first->update(['deadline_at' => now()->subMinutes(20)]);

        // Should succeed: the stale session is expired during create.
        $second = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 2,
        ]);

        $this->assertSame(QuizSession::STATUS_EXPIRED, $first->fresh()->status);
        $this->assertTrue($second->isInProgress());
    }

    // ---- Answering & sandbox ----

    public function test_answering_never_touches_student_answer_or_score(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);

        $session = $this->service->createSession($student, [
            'mode' => 'tutor', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);

        $q = $questions->first();
        $this->service->answerQuestion($session, $q->id, $this->correctAnswerId($q));

        $this->assertSame(0, StudentAnswer::count(), 'Quiz answers must not write StudentAnswer');
        $this->assertSame(0, StudentScore::count(), 'Quiz answers must not award league points');
        // ...but the quiz row IS updated.
        $this->assertTrue((bool) $session->questions()->where('question_id', $q->id)->first()->is_correct);
    }

    public function test_tutor_answer_locks_after_first_submission(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);
        $session = $this->service->createSession($student, [
            'mode' => 'tutor', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);
        $q = $questions->first();

        $this->service->answerQuestion($session, $q->id, $this->correctAnswerId($q));

        $this->expectException(ValidationException::class);
        $this->service->answerQuestion($session, $q->id, $this->wrongAnswerId($q));
    }

    public function test_exam_answer_can_be_overwritten(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);
        $session = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);
        $q = $questions->first();

        $this->service->answerQuestion($session, $q->id, $this->wrongAnswerId($q));
        $this->service->answerQuestion($session, $q->id, $this->correctAnswerId($q));

        $row = $session->questions()->where('question_id', $q->id)->first();
        $this->assertSame($this->correctAnswerId($q), $row->answer_id);
        $this->assertTrue((bool) $row->is_correct);
    }

    public function test_answer_must_belong_to_question(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);
        $session = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 3,
        ]);

        $foreignAnswer = $this->correctAnswerId($questions[1]);

        $this->expectException(ValidationException::class);
        $this->service->answerQuestion($session, $questions[0]->id, $foreignAnswer);
    }

    // ---- Completion & expiry ----

    public function test_complete_computes_score_and_is_idempotent(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 4);
        $session = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id], 'question_count' => 4,
        ]);

        // 2 correct, 1 wrong, 1 skipped.
        $ordered = $session->questions()->orderBy('order')->get();
        $byId = $questions->keyBy('id');
        $this->service->answerQuestion($session, $ordered[0]->question_id, $this->correctAnswerId($byId[$ordered[0]->question_id]));
        $this->service->answerQuestion($session, $ordered[1]->question_id, $this->correctAnswerId($byId[$ordered[1]->question_id]));
        $this->service->answerQuestion($session, $ordered[2]->question_id, $this->wrongAnswerId($byId[$ordered[2]->question_id]));

        $done = $this->service->completeSession($session);

        $this->assertSame(QuizSession::STATUS_COMPLETED, $done->status);
        $this->assertSame(2, $done->correct_count);
        $this->assertSame(1, $done->incorrect_count);
        $this->assertSame(1, $done->skipped_count);
        $this->assertEquals(50.0, (float) $done->score_percent);

        // Idempotent: a second complete must not change the result.
        $again = $this->service->completeSession($done->fresh());
        $this->assertSame(50.0, (float) $again->score_percent);
        $this->assertSame(QuizSession::STATUS_COMPLETED, $again->status);
    }

    public function test_expiry_finalizes_with_skipped_questions(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 3);
        $session = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id],
            'question_count' => 3, 'time_limit_minutes' => 5,
        ]);

        $session->update(['deadline_at' => now()->subMinutes(10)]);
        $synced = $this->service->syncExpiry($session->fresh());

        $this->assertSame(QuizSession::STATUS_EXPIRED, $synced->status);
        $this->assertSame(3, $synced->skipped_count);
        $this->assertEquals(0.0, (float) $synced->score_percent);
    }

    public function test_answering_expired_session_throws_conflict(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 3);
        $session = $this->service->createSession($student, [
            'mode' => 'exam', 'source' => 'random', 'category_ids' => [$category->id],
            'question_count' => 3, 'time_limit_minutes' => 5,
        ]);
        $session->update(['deadline_at' => now()->subMinutes(10)]);

        $this->expectException(\App\Exceptions\QuizConflictException::class);
        $this->service->answerQuestion($session->fresh(), $questions[0]->id, $this->correctAnswerId($questions[0]));
    }

    // ---- Daily challenge ----

    public function test_daily_challenge_is_idempotent_per_day(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 20);

        $a = $this->service->startDailyChallenge($student);
        $b = $this->service->startDailyChallenge($student);

        $this->assertSame($a->id, $b->id, 'Same day must reuse the same challenge session');
        $this->assertNotNull($a->challenge_date);
    }

    public function test_daily_completion_awards_league_points_once(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 20);

        $session = $this->service->startDailyChallenge($student);
        $this->service->completeSession($session);

        $this->assertSame(1, StudentScore::where('reason', 'daily_challenge')->count());
        $awarded = StudentScore::where('reason', 'daily_challenge')->sum('score');
        $this->assertGreaterThanOrEqual(QuizService::DAILY_BONUS_BASE, $awarded);

        // Re-completing must not double-award.
        $this->service->completeSession($session->fresh());
        $this->assertSame(1, StudentScore::where('reason', 'daily_challenge')->count());
    }

    public function test_daily_challenge_cannot_be_abandoned(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $this->makeQuestions($category, 20);
        $session = $this->service->startDailyChallenge($student);

        $this->expectException(ValidationException::class);
        $this->service->abandonSession($session);
    }

    public function test_streak_counts_consecutive_completed_days(): void
    {
        $student = $this->makeStudent();

        // Completed challenges today, yesterday, and 2 days ago.
        foreach ([0, 1, 2] as $daysAgo) {
            QuizSession::create([
                'student_id' => $student->id, 'mode' => 'exam', 'source' => 'random',
                'category_ids' => [], 'question_count' => 10, 'status' => QuizSession::STATUS_COMPLETED,
                'challenge_date' => now()->subDays($daysAgo)->toDateString(),
                'started_at' => now()->subDays($daysAgo), 'completed_at' => now()->subDays($daysAgo),
            ]);
        }

        $this->assertSame(3, $this->service->dailyStreak($student));
    }

    // ---- Exam simulation ----

    public function test_simulation_covers_every_section(): void
    {
        $student = $this->makeStudent();
        // Two sections in the student's exam, each with its own category + questions.
        $section1 = \App\Models\ExamSection::factory()->create(['exam_id' => $student->exam_id]);
        $section2 = \App\Models\ExamSection::factory()->create(['exam_id' => $student->exam_id]);
        $cat1 = $this->makeCategory($student->exam, $section1);
        $cat2 = $this->makeCategory($student->exam, $section2);
        $this->makeQuestions($cat1, 40);
        $this->makeQuestions($cat2, 40);

        $session = $this->service->startSimulation($student);

        $this->assertTrue((bool) $session->is_simulation);

        // Every section must contribute at least one question.
        $sectionIdsHit = \Illuminate\Support\Facades\DB::table('quiz_session_questions as qsq')
            ->join('category_questions as cq', 'cq.question_id', '=', 'qsq.question_id')
            ->join('section_categories as sc', 'sc.id', '=', 'cq.section_category_id')
            ->where('qsq.quiz_session_id', $session->id)
            ->distinct()
            ->pluck('sc.exam_section_id');

        $this->assertContains($section1->id, $sectionIdsHit->all());
        $this->assertContains($section2->id, $sectionIdsHit->all());
    }

    // ---- Practice-exam model run ----

    public function test_model_run_freezes_all_model_questions(): void
    {
        $student = $this->makeStudent();
        $category = $this->makeCategory($student->exam);
        $questions = $this->makeQuestions($category, 5);

        $model = \App\Models\PracticeExam::create(['title' => 'نموذج 2023', 'is_active' => true]);
        $questions->each(fn ($q) => $q->update(['practice_exam_id' => $model->id]));

        $session = $this->service->startFromModel($student, $model);

        $this->assertSame($model->id, $session->practice_exam_id);
        $this->assertSame('exam', $session->mode);
        $this->assertSame(5, $session->question_count);
        $this->assertNotNull($session->deadline_at, 'model run is timed');
        $this->assertEqualsCanonicalizing(
            $questions->pluck('id')->all(),
            $session->questions()->pluck('question_id')->all()
        );
    }

    public function test_inactive_or_empty_model_is_rejected(): void
    {
        $student = $this->makeStudent();
        $empty = \App\Models\PracticeExam::create(['title' => 'فارغ', 'is_active' => true]);

        $this->expectException(ValidationException::class);
        $this->service->startFromModel($student, $empty);
    }
}
