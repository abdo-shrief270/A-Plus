<?php

namespace Tests\Feature\Commerce;

use App\Models\Plan;
use App\Services\TrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class TrialTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private function trialPlan(int $days = 3): Plan
    {
        return Plan::create([
            'name' => 'التجربة المجانية', 'type' => 'trial',
            'price' => 0, 'points' => 0, 'duration_days' => $days, 'is_active' => true,
        ]);
    }

    public function test_new_student_is_granted_an_active_trial(): void
    {
        $this->trialPlan(3);

        // makeStudent() creates a Student, firing the observer that grants the trial.
        $student = $this->makeStudent();

        $sub = $student->subscriptions()->first();
        $this->assertNotNull($sub, 'a trial subscription should be granted on creation');
        $this->assertSame('active', $sub->status);
        $this->assertEqualsWithDelta(3, now()->diffInDays($sub->ends_at), 1);
        $this->assertTrue($student->fresh()->hasUnlimitedAccess(), 'trial grants unlimited access');
    }

    public function test_no_trial_granted_when_no_plan_configured(): void
    {
        $student = $this->makeStudent(); // no trial plan seeded
        $this->assertSame(0, $student->subscriptions()->count());
        $this->assertFalse($student->hasUnlimitedAccess());
    }

    public function test_trial_not_granted_twice(): void
    {
        $this->trialPlan();
        $student = $this->makeStudent();
        $this->assertSame(1, $student->subscriptions()->count());

        // Calling the service again must not stack a second trial.
        app(TrialService::class)->grantTo($student->fresh());
        $this->assertSame(1, $student->subscriptions()->count());
    }

    public function test_expired_trial_no_longer_grants_access(): void
    {
        $this->trialPlan();
        $student = $this->makeStudent();
        $student->subscriptions()->first()->update(['ends_at' => now()->subDay()]);

        $this->assertFalse($student->fresh()->hasUnlimitedAccess());
    }
}
