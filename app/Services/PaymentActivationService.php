<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Activates whatever a pending payment is for — enrollments, subscriptions,
 * or point packs — and credits wallet points. Shared by the API payment
 * confirm flow AND admin (Filament) manual activation, so both behave
 * identically while real gateways are disabled.
 */
class PaymentActivationService
{
    /**
     * Mark a pending payment paid and activate its linked records.
     * Idempotent: a non-pending payment is returned untouched.
     */
    public function activate(Payment $payment, string $method = 'manual'): Payment
    {
        if ($payment->status !== 'pending') {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $method) {
            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $method,
            ])->save();

            $kind = $payment->metadata['kind'] ?? null;

            if ($kind === 'enrollment') {
                $ids = $payment->metadata['enrollment_ids'] ?? [];
                Enrollment::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->update(['status' => 'active']);
            } elseif ($kind === 'subscription') {
                $this->activateSubscriptions($payment);
            }

            activity()
                ->causedBy($payment->user)
                ->performedOn($payment)
                ->event('payment_activated')
                ->withProperties([
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $method,
                ])
                ->log('تم تفعيل عملية الدفع');

            return $payment;
        });
    }

    private function activateSubscriptions(Payment $payment): void
    {
        $ids = $payment->metadata['subscription_ids'] ?? [];
        $subs = Subscription::whereIn('id', $ids)
            ->where('status', 'pending')
            ->with('plan')
            ->get();

        foreach ($subs as $sub) {
            $startsAt = now();
            $durationDays = $sub->plan?->duration_days;
            $sub->forceFill([
                'status' => 'active',
                'starts_at' => $sub->starts_at ?? $startsAt,
                'ends_at' => $sub->ends_at
                    ?? ($durationDays ? $startsAt->copy()->addDays($durationDays) : null),
            ])->save();

            // Credit the plan's points to the student's wallet (point packs).
            $points = (int) ($sub->plan?->points ?? 0);
            if ($points > 0 && $sub->student_id) {
                $wallet = Wallet::firstOrCreate(['student_id' => $sub->student_id], ['balance' => 0]);
                $wallet->increment('balance', $points);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'amount' => $points,
                    'type' => $sub->plan?->type === 'pack' ? 'pack_purchase' : 'subscription_grant',
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                ]);
            }
        }
    }
}
