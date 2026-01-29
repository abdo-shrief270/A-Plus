<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'payment_method' => 'required|string', // visa, mastercard, etc.
        ]);

        $enrollment = Enrollment::findOrFail($request->enrollment_id);
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($enrollment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // In a real app, we would talk to Gateway here.
        // For simulation, we create a pending payment.

        $payment = Payment::create([
            'transaction_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'enrollment_id' => $enrollment->id,
            'amount' => 100.00, // Should fetch from enrollment calculation logic or pass from front (insecure)
            // Ideally Enrollment should store 'pending_amount' or we recalculate here.
            // For MVP, we presume the frontend passes the amount OR we re-calculate.
            // Let's just use dummy 100 for now or fetch course price.
            'currency' => 'SAR',
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        // Simulating a Redirect URL for frontend
        return response()->json([
            'message' => 'Payment initiated',
            'data' => [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'redirect_url' => "http://aplus.test/payment/fake-gateway/{$payment->transaction_id}",
            ]
        ]);
    }

    public function webhook(Request $request)
    {
        // Handle Gateway Webhook to update Payment and Enrollment status
        // Verify signature
        // Update Payment status -> paid
        // Update Enrollment status -> active
    }
}
