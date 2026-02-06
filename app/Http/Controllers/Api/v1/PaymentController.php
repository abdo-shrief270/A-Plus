<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class PaymentController extends BaseApiController
{
    protected $gateway;
    protected $walletService;
    protected $subscriptionService;

    public function __construct(
        PaymentGatewayInterface $gateway,
        WalletService $walletService,
        SubscriptionService $subscriptionService
    ) {
        $this->gateway = $gateway;
        $this->walletService = $walletService;
        $this->subscriptionService = $subscriptionService;
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|string',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = auth('api')->user();

        // 1. Initiate Payment via Gateway
        $paymentData = $this->gateway->initiatePayment($plan->price, 'SAR', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        // 2. We might want to store a pending "Payment" record here in DB 
        // to track the transaction ID against the user/plan.
        // For brevity, skipping that step, but in production we MUST do it.

        return $this->successResponse($paymentData, 'Payment initiated');
    }

    public function callback(Request $request)
    {
        // This endpoint receives the redirect from the Payment Gateway
        $verification = $this->gateway->verifyPayment($request->all());

        if ($verification['status'] === 'paid') {
            // Fulfill the order
            $this->fulfillOrder($request->all()); // Pass data needed to identify plan/user

            // Note: In real world, we rely on Webhook for fulfillment, 
            // callback is just for UI redirection. 
            // But for Mock, we might do it here.

            return $this->successResponse(['status' => 'paid'], 'Payment successful');
        }

        return $this->errorResponse('Payment failed', 400);
    }

    // Mock helper to fulfill (simulates webhook logic)
    protected function fulfillOrder($data)
    {
        // Extract user and plan from temp storage or passed params
        // ensuring security.

        // For the mock, we assume we can get user/plan IDs somehow or just hardcode for valid test.
        // Real imp: Transaction ID lookup in DB -> get Plan -> call Service.
    }
}
