<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    public function subscribe(User $user, Plan $plan): Subscription
    {
        // Cancel existing active subscriptions? Or allow concurrent?
        // Assuming one active subscription allowed for simplicity, or we treat them additive.
        // Let's assume replacement for now.

        $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => Carbon::now(),
            'ends_at' => $plan->duration_days ? Carbon::now()->addDays($plan->duration_days) : null,
            'status' => 'active',
        ]);
    }
}
