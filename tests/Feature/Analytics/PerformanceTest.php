<?php

namespace Tests\Feature\Analytics;

use App\Models\Question;
use App\Models\StudentAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class PerformanceTest extends TestCase
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

    private function answer($student, Question $question, bool $correct): void
    {
        StudentAnswer::create([
            'user_id' => $student->user->id,
            'student_id' => $student->id,
            'question_id' => $question->id,
            'answer_id' => $correct ? $this->correctAnswerId($question) : $this->wrongAnswerId($question),
            'is_correct' => $correct,
            'score_earned' => $correct ? 1 : 0,
        ]);
    }

    public function test_weakest_and_strongest_categories_are_separated_by_accuracy(): void
    {
        $student = $this->makeStudent();
        $weak = $this->makeCategory($student->exam);
        $strong = $this->makeCategory($student->exam);
        $weakQs = $this->makeQuestions($weak, 4);
        $strongQs = $this->makeQuestions($strong, 4);

        // Weak category: 1/4 correct. Strong: 4/4 correct.
        $weakQs->each(fn ($q, $i) => $this->answer($student, $q, $i === 0));
        $strongQs->each(fn ($q) => $this->answer($student, $q, true));

        $res = $this->actingAs($student->user, 'api')->getJson('/api/v2/performance')->assertOk();
        $cats = $res->json('data.categories');

        $this->assertSame($weak->id, $cats['weakest'][0]['category_id']);
        $this->assertSame($strong->id, $cats['strongest'][0]['category_id']);
        $this->assertEquals(25, $cats['weakest'][0]['accuracy']);
        $this->assertEquals(100, $cats['strongest'][0]['accuracy']);
    }

    public function test_categories_below_min_attempts_are_excluded(): void
    {
        $student = $this->makeStudent();
        $cat = $this->makeCategory($student->exam);
        $qs = $this->makeQuestions($cat, 4);
        // Only 2 attempts — under MIN_ATTEMPTS (3).
        $this->answer($student, $qs[0], true);
        $this->answer($student, $qs[1], false);

        $res = $this->actingAs($student->user, 'api')->getJson('/api/v2/performance')->assertOk();
        $this->assertSame(0, $res->json('data.categories.evaluated_count'));
    }

    public function test_totals_and_trend_window_shape(): void
    {
        $student = $this->makeStudent();
        $cat = $this->makeCategory($student->exam);
        $qs = $this->makeQuestions($cat, 4);
        $qs->each(fn ($q, $i) => $this->answer($student, $q, $i < 3)); // 3/4 correct

        $res = $this->actingAs($student->user, 'api')->getJson('/api/v2/performance?days=14')->assertOk();

        $this->assertSame(4, $res->json('data.totals.answered'));
        $this->assertEquals(75, $res->json('data.totals.accuracy'));
        $this->assertCount(14, $res->json('data.accuracy_trend'));
    }
}
