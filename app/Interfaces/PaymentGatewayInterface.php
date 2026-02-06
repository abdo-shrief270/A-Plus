<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment request.
     * Returns an array with 'transaction_id', 'redirect_url', and 'payload'.
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = []): array;

    /**
     * Verify a payment callback/webhook.
     * Returns the transaction ID and status ('paid', 'failed', 'pending').
     */
    public function verifyPayment(array $data): array;
}
