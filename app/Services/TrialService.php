<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Student;
use App\Models\Subscription;

/**
 * Grants a free, time-limited trial subscription to new students so they get
 * full (unlimited) content access for a short window without paying. Driven by
 * a Plan of type "trial" (price 0); its duration_days controls the window.
 */
class TrialService
{
    /**
     * Grant the active trial plan to a student. Idempotent and safe:
     * - no-op if no active trial plan is configured (e.g. fresh test DB),
     * - no-op if the student already has any subscription (paid or trial).
     */
    public function grantTo(Student $student): ?Subscription
    {
        if ($student->subscriptions()->exists()) {
            return null;
        }

        $plan = $this->trialPlan();
        if (!$plan) {
            return null;
        }

        $now = now();
        $days = (int) ($plan->duration_days ?: 3);

        return $student->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $now,
            'ends_at' => $now->copy()->addDays($days),
        ]);
    }

    /** The single active trial plan, if one is configured. */
    public function trialPlan(): ?Plan
    {
        return Plan::where('type', 'trial')->where('is_active', true)->first();
    }
}
