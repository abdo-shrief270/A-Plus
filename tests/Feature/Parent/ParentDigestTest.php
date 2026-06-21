<?php

namespace Tests\Feature\Parent;

use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentParent;
use App\Models\User;
use App\Services\ParentWeeklyDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class ParentDigestTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private ParentWeeklyDigestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ParentWeeklyDigestService::class);
    }

    private function answer($student, Question $question, bool $correct, $at): void
    {
        $answer = StudentAnswer::create([
            'user_id' => $student->user->id, 'student_id' => $student->id,
            'question_id' => $question->id,
            'answer_id' => $correct ? $this->correctAnswerId($question) : $this->wrongAnswerId($question),
            'is_correct' => $correct, 'score_earned' => 0,
        ]);
        // created_at isn't fillable; backdate it explicitly (save honors the dirty timestamp).
        $answer->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    public function test_child_summary_counts_only_activity_in_window(): void
    {
        $student = $this->makeStudent();
        $cat = $this->makeCategory($student->exam);
        $qs = $this->makeQuestions($cat, 4);
        $since = now()->subDays(7);

        $this->answer($student, $qs[0], true, now()->subDay());    // in window
        $this->answer($student, $qs[1], true, now()->subDays(2));  // in window
        $this->answer($student, $qs[2], false, now()->subDays(3)); // in window
        $this->answer($student, $qs[3], true, now()->subDays(20)); // OUT of window

        $summary = $this->service->childSummary($student, $since);

        $this->assertSame(3, $summary['answered']);
        $this->assertSame(2, $summary['correct']);
        $this->assertSame(66.7, $summary['accuracy']);
        $this->assertTrue($summary['was_active']);
    }

    public function test_inactive_child_headline(): void
    {
        $student = $this->makeStudent();
        $summary = $this->service->childSummary($student, now()->subDays(7));

        $this->assertFalse($summary['was_active']);
        $this->assertStringContainsString('لا نشاط', $this->service->childHeadline($summary));
    }

    public function test_summaries_for_parent_covers_all_linked_children(): void
    {
        $parent = User::factory()->create();
        $childA = $this->makeStudent();
        $childB = $this->makeStudent();
        StudentParent::create(['parent_id' => $parent->id, 'student_id' => $childA->id]);
        StudentParent::create(['parent_id' => $parent->id, 'student_id' => $childB->id]);

        $summaries = $this->service->summariesForParent($parent, now()->subDays(7));

        $this->assertCount(2, $summaries);
        $this->assertEqualsCanonicalizing(
            [$childA->id, $childB->id],
            array_column($summaries, 'student_id')
        );
    }
}
