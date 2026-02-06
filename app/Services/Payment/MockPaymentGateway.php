<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function initiatePayment(float $amount, string $currency, array $metadata = []): array
    {
        $transactionId = (string) Str::uuid();

        // In a real scenario, this URL would go to Stripe/PayPal.
        // Here we simulate a page that just redirects back to our callback with success.
        $redirectUrl = route('api.v1.payment.callback.mock', [
            'transaction_id' => $transactionId,
            'status' => 'success'
        ]);

        return [
            'transaction_id' => $transactionId,
            'redirect_url' => $redirectUrl,
            'payload' => ['mock_data' => 'test'],
        ];
    }

    public function verifyPayment(array $data): array
    {
        // Mock verification logic
        $status = $data['status'] === 'success' ? 'paid' : 'failed';

        return [
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => $status,
        ];
    }
}
