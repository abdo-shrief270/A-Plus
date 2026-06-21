<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces that the request comes from the user's currently approved+active
 * device. Any deviation returns 401 with a reason-specific message; the
 * frontend interceptor catches these and signs the user out.
 */
class EnforceSingleDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        if (!$user) {
            return $next($request);
        }

        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        if (!$deviceId) {
            return response()->json([
                'status' => 401,
                'message' => 'معرف الجهاز مطلوب',
                'reason' => 'device_id_required',
            ], 401);
        }

        $device = $user->devices()->where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'status' => 401,
                'message' => 'هذا الجهاز غير مسجل لحسابك.',
                'reason' => 'device_not_registered',
            ], 401);
        }

        if (!$device->is_trusted) {
            return response()->json([
                'status' => 401,
                'message' => 'هذا الجهاز محظور. يرجى التواصل مع الدعم.',
                'reason' => 'device_blocked',
            ], 401);
        }

        if (!$device->is_approved) {
            return response()->json([
                'status' => 401,
                'message' => 'هذا الجهاز قيد المراجعة من قبل الإدارة.',
                'reason' => 'device_pending_approval',
            ], 401);
        }

        if (!$device->is_current) {
            return response()->json([
                'status' => 401,
                'message' => 'تم تسجيل الدخول من جهاز آخر، تم إنهاء جلستك على هذا الجهاز.',
                'reason' => 'device_taken_over',
            ], 401);
        }

        return $next($request);
    }
}
