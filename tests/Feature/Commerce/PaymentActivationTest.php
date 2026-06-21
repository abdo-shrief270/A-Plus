<?php

namespace Tests\Feature\Commerce;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Services\PaymentActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class PaymentActivationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private PaymentActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentActivationService::class);
    }

    public function test_activating_subscription_payment_makes_it_active_and_credits_points(): void
    {
        $student = $this->makeStudent();
        $plan = Plan::create(['name' => 'شهري', 'type' => 'subscription', 'price' => 50, 'points' => 200, 'duration_days' => 30, 'is_active' => true]);
        $sub = Subscription::create(['student_id' => $student->id, 'plan_id' => $plan->id, 'status' => 'pending', 'starts_at' => now()]);
        $payment = Payment::create([
            'transaction_id' => 'TX-1', 'user_id' => $student->user->id, 'amount' => 50,
            'currency' => 'SAR', 'status' => 'pending', 'payment_method' => 'pending',
            'metadata' => ['kind' => 'subscription', 'subscription_ids' => [$sub->id]],
        ]);

        $this->service->activate($payment, 'manual');

        $this->assertSame('paid', $payment->fresh()->status);
        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->ends_at);
        $this->assertSame(200, (int) Wallet::where('student_id', $student->id)->value('balance'));
    }

    public function test_activation_is_idempotent(): void
    {
        $student = $this->makeStudent();
        $plan = Plan::create(['name' => 'شهري', 'type' => 'subscription', 'price' => 50, 'points' => 200, 'duration_days' => 30, 'is_active' => true]);
        $sub = Subscription::create(['student_id' => $student->id, 'plan_id' => $plan->id, 'status' => 'pending', 'starts_at' => now()]);
        $payment = Payment::create([
            'transaction_id' => 'TX-2', 'user_id' => $student->user->id, 'amount' => 50,
            'currency' => 'SAR', 'status' => 'pending', 'payment_method' => 'pending',
            'metadata' => ['kind' => 'subscription', 'subscription_ids' => [$sub->id]],
        ]);

        $this->service->activate($payment);
        $this->service->activate($payment->fresh()); // second call must not double-credit

        $this->assertSame(200, (int) Wallet::where('student_id', $student->id)->value('balance'));
        $this->assertSame('active', $sub->fresh()->status);
    }

    public function test_point_pack_credits_wallet_without_subscription_duration(): void
    {
        $student = $this->makeStudent();
        $pack = Plan::create(['name' => 'باقة نقاط', 'type' => 'pack', 'price' => 20, 'points' => 500, 'duration_days' => null, 'is_active' => true]);
        $sub = Subscription::create(['student_id' => $student->id, 'plan_id' => $pack->id, 'status' => 'pending', 'starts_at' => now()]);
        $payment = Payment::create([
            'transaction_id' => 'TX-3', 'user_id' => $student->user->id, 'amount' => 20,
            'currency' => 'SAR', 'status' => 'pending', 'payment_method' => 'pending',
            'metadata' => ['kind' => 'subscription', 'subscription_ids' => [$sub->id]],
        ]);

        $this->service->activate($payment);

        $this->assertSame(500, (int) Wallet::where('student_id', $student->id)->value('balance'));
        $this->assertNull($sub->fresh()->ends_at, 'a points pack grants no subscription window');
    }
}
