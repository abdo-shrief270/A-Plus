<?php

namespace Tests\Feature\Plan;

use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Services\StudyPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class StudyPlanTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private StudyPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StudyPlanService::class);
    }

    private function makeLessons($student, int $count): \Illuminate\Support\Collection
    {
        return collect(range(1, $count))->map(fn ($i) => Lesson::factory()->create([
            'exam_id' => $student->exam_id,
            'is_active' => true,
            'order' => $i,
        ]));
    }

    public function test_plan_generates_and_groups_into_weeks(): void
    {
        $student = $this->makeStudent();
        $student->update(['exam_date' => now()->addDays(14)]);
        $this->makeLessons($student, 10);

        $this->assertTrue($this->service->ensurePlan($student));

        $weeks = $this->service->getGroupedPlan($student);
        $this->assertNotEmpty($weeks);
        // Each week carries a date range + nested days, and totals add up.
        $totalLessons = collect($weeks)->sum('total_lessons');
        $this->assertSame(10, $totalLessons);
        $this->assertNotNull($weeks[0]['start_date']);
        $this->assertArrayHasKey('days', $weeks[0]);
    }

    public function test_future_day_lessons_are_locked(): void
    {
        $student = $this->makeStudent();
        $student->update(['exam_date' => now()->addDays(14)]);
        $lessons = $this->makeLessons($student, 2);

        // One scheduled today (open), one in the future (locked).
        StudentLessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lessons[0]->id, 'scheduled_date' => now(), 'status' => 'pending']);
        StudentLessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lessons[1]->id, 'scheduled_date' => now()->addDays(3), 'status' => 'pending']);

        $weeks = $this->service->getGroupedPlan($student);
        $allLessons = collect($weeks)->flatMap(fn ($w) => collect($w['days'])->flatMap->lessons);

        $today = $allLessons->firstWhere('lesson_id', $lessons[0]->id);
        $future = $allLessons->firstWhere('lesson_id', $lessons[1]->id);

        $this->assertFalse($today['is_locked'], "today's lesson must be open");
        $this->assertTrue($future['is_locked'], 'future lesson must be locked');
    }

    public function test_reminder_counts_separate_due_and_overdue(): void
    {
        $student = $this->makeStudent();
        $lessons = $this->makeLessons($student, 3);

        StudentLessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lessons[0]->id, 'scheduled_date' => now()->subDays(2), 'status' => 'pending']); // overdue
        StudentLessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lessons[1]->id, 'scheduled_date' => now(), 'status' => 'pending']); // due today
        StudentLessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lessons[2]->id, 'scheduled_date' => now()->subDay(), 'status' => 'completed']); // done — ignored

        $counts = $this->service->reminderCounts($student);
        $this->assertSame(1, $counts['overdue']);
        $this->assertSame(1, $counts['due_today']);
    }

    public function test_regenerate_preserves_completed_status(): void
    {
        $student = $this->makeStudent();
        $student->update(['exam_date' => now()->addDays(10)]);
        $lessons = $this->makeLessons($student, 4);
        $this->service->ensurePlan($student);

        // Complete one lesson, then regenerate.
        $progress = StudentLessonProgress::where('student_id', $student->id)->first();
        $progress->update(['status' => 'completed', 'completed_at' => now()]);

        $this->service->generateStudyPlan($student);

        $this->assertSame(
            1,
            StudentLessonProgress::where('student_id', $student->id)->where('status', 'completed')->count(),
            'regeneration must preserve completed lessons'
        );
    }

    public function test_lesson_show_blocks_future_lesson_via_api(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\JwtMiddleware::class,
            \App\Http\Middleware\EnforceSingleDevice::class,
        ]);

        $student = $this->makeStudent();
        $lesson = Lesson::factory()->create(['exam_id' => $student->exam_id, 'is_active' => true, 'order' => 1]);
        StudentLessonProgress::create([
            'student_id' => $student->id, 'lesson_id' => $lesson->id,
            'scheduled_date' => now()->addDays(3), 'status' => 'pending',
        ]);

        $this->actingAs($student->user, 'api')
            ->getJson("/api/v2/lessons/{$lesson->id}")
            ->assertStatus(403);
    }
}
