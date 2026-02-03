<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends BaseApiController
{
    public function enroll(Request $request, Course $course)
    {
        $request->validate([
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ]);

        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check if already enrolled
        if (Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->active()->exists()) {
            return $this->errorResponse('Already enrolled', 400);
        }

        $price = $course->price;
        $coupon = null;
        $discountAmount = 0;

        // Apply Coupon
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();

            if (!$coupon->isValid()) {
                return $this->errorResponse('Invalid or expired coupon', 400);
            }

            // Check if user already used this coupon (if limit per user exists - schema doesn't strict this but logical)
            // For now just check global limits in isValid()

            $discountAmount = $coupon->calculateDiscount($price);
            $price = max(0, $price - $discountAmount);
        }

        try {
            DB::beginTransaction();

            // Create Enrollment (Pending Payment)
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => $price == 0 ? 'active' : 'pending', // Free courses auto-activate
                'enrolled_at' => now(),
                'expires_at' => $course->end_date, // or based on course duration
                'created_by' => 'student',
            ]);

            // If price is 0, activate immediately
            if ($price == 0) {
                if ($coupon) {
                    $coupon->increment('times_used');
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => $user->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }
                DB::commit();
                return $this->successResponse($enrollment, 'Enrolled successfully');
            }

            // If Paid, return Enrollment ID and Amount for Payment Gateway
            DB::commit();

            return $this->successResponse([
                'enrollment_id' => $enrollment->id,
                'amount' => $price,
                'currency' => 'SAR',
                'coupon_applied' => $coupon ? $coupon->code : null,
            ], 'Enrollment initiated. Proceed to payment.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Enrollment failed: ' . $e->getMessage(), 500);
        }
    }

    public function bulkEnroll(Request $request)
    {
        // Placeholder for school bulk enrollment logic
        // Validate request: course_id, student_ids array
        // Calculate total price with optional coupon
        // Associate students
    }
}
