<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\v2\CreateSubscriptionsRequest;
use App\Http\Resources\v2\SubscriptionResource;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentSchool;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends BaseApiController
{
    /**
     * List Subscriptions (قائمة اشتراكات الباقات)
     *
     * يجلب اشتراكات الطلاب في الباقات. مفلتر تلقائياً بحسب الدور:
     * - ولي الأمر يرى اشتراكات أبنائه فقط.
     * - المدرسة ترى اشتراكات طلابها فقط.
     * - الطالب يرى اشتراكاته الخاصة.
     *
     * @queryParam per_page integer optional Default 20
     *
     * @group Dashboard / Subscriptions (الاشتراكات)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = Subscription::query()
            ->with(['plan', 'student.user'])
            ->orderByDesc('created_at');

        if ($user) {
            $studentIds = $this->scopedStudentIds($user);
            if ($user->type === 'student') {
                $studentIds = $user->student?->id ? [$user->student->id] : [];
            }
            if (in_array($user->type, ['parent', 'school', 'student'], true)) {
                $query->whereIn('student_id', $studentIds);
            }
        }

        $subscriptions = $query->paginate($request->input('per_page', 20));

        return $this->successResponse(
            SubscriptionResource::collection($subscriptions)->response()->getData(true),
            'Subscriptions retrieved successfully'
        );
    }

    /**
     * Subscribe Students to a Plan (اشتراك الطلاب في باقة)
     *
     * ينشئ اشتراكات لطلاب متعددين في باقة محددة. مسموح فقط للطلاب الذين تحت إشراف
     * المستخدم (أبناء ولي الأمر، أو طلاب المدرسة). يتم تخطي الطلاب الذين لديهم
     * اشتراك نشط (status='active') في نفس الباقة.
     *
     * @bodyParam plan_id integer required Example: 2
     * @bodyParam student_ids array required
     *
     * @group Dashboard / Subscriptions (الاشتراكات)
     * @unauthenticated false
     */
    public function store(CreateSubscriptionsRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $planId = (int) $request->input('plan_id');
        $studentIds = array_map('intval', $request->input('student_ids', []));

        $accessibleIds = $this->scopedStudentIds($user);
        $unauthorized = array_diff($studentIds, $accessibleIds);
        if (!empty($unauthorized)) {
            return $this->errorResponse('Some students are not under your account', 403);
        }

        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active) {
            return $this->errorResponse('Plan not available', 404);
        }

        return DB::transaction(function () use ($user, $plan, $studentIds) {
            $created = [];
            $skipped = [];

            foreach ($studentIds as $sid) {
                if ($plan->type === 'subscription') {
                    // Block if the student already has ANY active subscription-type plan.
                    // Pack plans don't count — packs can stack.
                    $alreadyOnSub = Subscription::where('student_id', $sid)
                        ->where('status', 'active')
                        ->whereHas('plan', fn ($q) => $q->where('type', 'subscription'))
                        ->exists();
                    if ($alreadyOnSub) {
                        $skipped[] = ['student_id' => $sid, 'reason' => 'already on a subscription plan'];
                        continue;
                    }
                }
                // Pack plans: no uniqueness check — students can buy the same/different packs repeatedly.

                $startsAt = now();
                $endsAt = $plan->duration_days
                    ? $startsAt->copy()->addDays((int) $plan->duration_days)
                    : null;

                $sub = Subscription::create([
                    'student_id' => $sid,
                    'plan_id' => $plan->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    // Pending until payment confirms. Free plans skip payment.
                    'status' => $plan->price > 0 ? 'pending' : 'active',
                ]);
                $created[] = ['student_id' => $sid, 'subscription_id' => $sub->id];
            }

            $payment = null;
            $totalAmount = (float) $plan->price * count($created);

            if (!empty($created) && $totalAmount > 0) {
                $payment = Payment::create([
                    'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                    'user_id' => $user->id,
                    'subscription_id' => $created[0]['subscription_id'],
                    'amount' => $totalAmount,
                    'description' => 'اشتراك في باقة: ' . $plan->name,
                    'currency' => 'SAR',
                    'payment_method' => 'pending',
                    'status' => 'pending',
                    'metadata' => [
                        'kind' => 'subscription',
                        'plan_id' => $plan->id,
                        'subscription_ids' => array_column($created, 'subscription_id'),
                    ],
                ]);
            }

            return $this->successResponse([
                'total_created' => count($created),
                'total_skipped' => count($skipped),
                'created' => $created,
                'skipped' => $skipped,
                'payment' => $payment ? [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ] : null,
                'requires_payment' => $payment !== null,
            ], 'Subscriptions processed');
        });
    }

    private function scopedStudentIds($user): array
    {
        if (!$user) return [];

        if ($user->type === 'parent') {
            return $user->studentParent()->pluck('student_id')->toArray();
        }

        if ($user->type === 'school') {
            $schoolId = $user->school?->id;
            if (!$schoolId) return [];
            return StudentSchool::where('school_id', $schoolId)
                ->pluck('student_id')
                ->toArray();
        }

        if ($user->type === 'student') {
            return $user->student?->id ? [$user->student->id] : [];
        }

        return [];
    }
}
