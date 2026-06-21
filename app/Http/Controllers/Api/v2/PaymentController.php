<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\PaymentResource;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends BaseApiController
{
    /**
     * List My Payments (سجل المدفوعات)
     *
     * @queryParam status string optional `pending\|paid\|failed\|refunded`. Example: paid
     * @queryParam per_page integer optional Default 15
     *
     * @group Payments (المدفوعات)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = Payment::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $payments = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            PaymentResource::collection($payments)->response()->getData(true),
            'Payments retrieved successfully'
        );
    }

    /**
     * Get Payment Detail (تفاصيل عملية الدفع)
     *
     * @group Payments (المدفوعات)
     * @unauthenticated false
     */
    public function show(Payment $payment): JsonResponse
    {
        $user = auth('api')->user();
        if ((int) $payment->user_id !== (int) $user->id) {
            return $this->errorResponse('غير مصرح', 403);
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }

    /**
     * Confirm Payment (mock gateway success). Activates the linked
     * enrollments / subscriptions stored in metadata.
     *
     * @bodyParam payment_method string optional Example: visa
     *
     * @group Payments (المدفوعات)
     * @unauthenticated false
     */
    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        // Self-service payment confirmation is unavailable while gateways are
        // disabled — activation is handled by an admin from the dashboard.
        if (!config('payments.enabled')) {
            return $this->errorResponse(
                'الدفع الإلكتروني غير متاح حالياً. سيتم تفعيل طلبك من قبل الإدارة بعد إتمام الدفع.',
                \Symfony\Component\HttpFoundation\Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $user = auth('api')->user();
        if ((int) $payment->user_id !== (int) $user->id) {
            return $this->errorResponse('غير مصرح', 403);
        }
        if ($payment->status !== 'pending') {
            return $this->errorResponse('عملية الدفع غير قابلة للتأكيد', 422);
        }

        $request->validate([
            'payment_method' => 'sometimes|string|max:32',
        ]);

        return DB::transaction(function () use ($payment, $request) {
            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $request->input('payment_method', 'mock'),
            ])->save();

            $kind = $payment->metadata['kind'] ?? null;

            if ($kind === 'enrollment') {
                $ids = $payment->metadata['enrollment_ids'] ?? [];
                Enrollment::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->update(['status' => 'active']);
            } elseif ($kind === 'subscription') {
                $ids = $payment->metadata['subscription_ids'] ?? [];
                $subs = Subscription::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->with('plan')
                    ->get();

                foreach ($subs as $sub) {
                    $sub->forceFill(['status' => 'active'])->save();

                    // Credit the plan's points to the student's wallet so subscribing
                    // to a points pack actually grants the points immediately.
                    $points = (int) ($sub->plan?->points ?? 0);
                    if ($points > 0 && $sub->student_id) {
                        $wallet = Wallet::firstOrCreate(
                            ['student_id' => $sub->student_id],
                            ['balance' => 0]
                        );
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

            activity()
                ->causedBy($payment->user)
                ->performedOn($payment)
                ->event('payment_succeeded')
                ->withProperties([
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'description' => $payment->description,
                ])
                ->log('تم إتمام عملية دفع بنجاح');

            if ($payment->user) {
                $payment->user->notify(new \App\Notifications\SimpleNotification(
                    title: 'تم الدفع بنجاح',
                    description: ($payment->description ? $payment->description . ' — ' : '')
                        . number_format((float) $payment->amount, 0) . ' ' . ($payment->currency ?: 'SAR'),
                    link: '/dashboard/payments',
                    color: 'success',
                    icon: 'i-heroicons-credit-card',
                ));
            }

            return $this->successResponse(
                new PaymentResource($payment->fresh()),
                'تم تأكيد الدفع بنجاح'
            );
        });
    }

    /**
     * Cancel Payment. Marks the payment as failed and cancels the linked
     * enrollments / subscriptions.
     *
     * @group Payments (المدفوعات)
     * @unauthenticated false
     */
    public function cancel(Payment $payment): JsonResponse
    {
        $user = auth('api')->user();
        if ((int) $payment->user_id !== (int) $user->id) {
            return $this->errorResponse('غير مصرح', 403);
        }
        if (!in_array($payment->status, ['pending'], true)) {
            return $this->errorResponse('لا يمكن إلغاء هذه العملية', 422);
        }

        return DB::transaction(function () use ($payment) {
            $payment->forceFill(['status' => 'failed'])->save();

            $kind = $payment->metadata['kind'] ?? null;
            if ($kind === 'enrollment') {
                $ids = $payment->metadata['enrollment_ids'] ?? [];
                Enrollment::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            } elseif ($kind === 'subscription') {
                $ids = $payment->metadata['subscription_ids'] ?? [];
                Subscription::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }

            return $this->successResponse(
                new PaymentResource($payment->fresh()),
                'تم إلغاء عملية الدفع'
            );
        });
    }
}
