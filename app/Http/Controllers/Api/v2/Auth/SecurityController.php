<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityController extends BaseApiController
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * Get Security Status (حالة الأمان)
     *
     * يُرجِع حالة 2FA والتحقق من البريد والهاتف للمستخدم الحالي.
     *
     * @group Auth / Security (الأمان)
     * @unauthenticated false
     */
    public function status(): JsonResponse
    {
        $user = auth('api')->user();

        return $this->successResponse(
            $this->serializeStatus($user),
            'Security status retrieved'
        );
    }

    /**
     * Send Security OTP (إرسال رمز التحقق)
     *
     * يُرسل رمز تحقق إلى البريد أو الهاتف ليتم استخدامه في إجراءات الأمان
     * (تفعيل 2FA، إيقاف 2FA، التحقق من البريد، التحقق من الهاتف).
     *
     * @bodyParam method string required إحدى القيم: `email`, `sms`, `whatsapp`. Example: email
     *
     * @group Auth / Security (الأمان)
     * @unauthenticated false
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'method' => 'required|in:email,sms,whatsapp',
        ]);

        $user = auth('api')->user();

        if ($request->method === 'email' && !$user->email) {
            return $this->errorResponse('لا يوجد بريد إلكتروني مسجل في الحساب', 422);
        }
        if (in_array($request->method, ['sms', 'whatsapp'], true) && !$user->phone) {
            return $this->errorResponse('لا يوجد رقم هاتف مسجل في الحساب', 422);
        }

        $result = $this->otpService->generate($user, $request->method);

        return $this->successResponse([
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
            'method' => $request->method,
            'sent_to' => $request->method === 'email'
                ? $this->maskEmail($user->email)
                : $this->maskPhone($user->phone),
        ], 'تم إرسال رمز التحقق');
    }

    /**
     * Confirm Security Action (تأكيد إجراء الأمان)
     *
     * يُحقّق رمز التحقق ويُنفّذ الإجراء المطلوب: تفعيل 2FA / إيقاف 2FA /
     * التحقق من البريد / التحقق من الهاتف.
     *
     * @bodyParam token string required الرمز المُسلَّم من نقطة send-otp.
     * @bodyParam otp string required الرمز السري (6 أرقام).
     * @bodyParam purpose string required `verify_email`, `verify_phone`, `enable_2fa`, `disable_2fa`.
     *
     * @group Auth / Security (الأمان)
     * @unauthenticated false
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'otp' => 'required|string',
            'purpose' => 'required|in:verify_email,verify_phone,verify_whatsapp,enable_2fa,disable_2fa',
        ]);

        $verify = $this->otpService->verify($request->token, $request->otp);
        if (!$verify['valid']) {
            return $this->errorResponse($verify['message'] ?? 'رمز التحقق غير صحيح', 401);
        }

        $authedUser = auth('api')->user();
        if ((int) $verify['user_id'] !== (int) $authedUser->id) {
            return $this->errorResponse('رمز التحقق لا يخص هذا الحساب', 403);
        }

        $user = User::find($authedUser->id);

        switch ($request->purpose) {
            case 'verify_email':
                if (!$user->email) {
                    return $this->errorResponse('لا يوجد بريد إلكتروني', 422);
                }
                $user->forceFill(['email_verified_at' => now()])->save();
                break;

            case 'verify_phone':
                if (!$user->phone) {
                    return $this->errorResponse('لا يوجد رقم هاتف', 422);
                }
                $user->forceFill(['phone_verified_at' => now()])->save();
                break;

            case 'verify_whatsapp':
                if (!$user->phone) {
                    return $this->errorResponse('لا يوجد رقم هاتف', 422);
                }
                $user->forceFill(['whatsapp_verified_at' => now()])->save();
                break;

            case 'enable_2fa':
                $user->forceFill(['2fa' => true])->save();
                break;

            case 'disable_2fa':
                $user->forceFill(['2fa' => false])->save();
                break;
        }

        $this->otpService->consume($request->token);

        $logMessage = match ($request->purpose) {
            'verify_email' => 'تم تأكيد البريد الإلكتروني',
            'verify_phone' => 'تم تأكيد رقم الهاتف (SMS)',
            'verify_whatsapp' => 'تم تأكيد رقم واتساب',
            'enable_2fa' => 'تم تفعيل المصادقة الثنائية',
            'disable_2fa' => 'تم إيقاف المصادقة الثنائية',
            default => 'إجراء أمان',
        };
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event($request->purpose)
            ->log($logMessage);

        return $this->successResponse(
            $this->serializeStatus($user->fresh()),
            'تم تنفيذ الإجراء بنجاح'
        );
    }

    private function serializeStatus(User $user): array
    {
        return [
            'two_factor_enabled' => (bool) $user->{'2fa'},
            'email_verified' => !is_null($user->email_verified_at),
            'phone_verified' => !is_null($user->phone_verified_at),
            'whatsapp_verified' => !is_null($user->whatsapp_verified_at),
            'email' => $user->email,
            'phone' => $user->phone,
            'masked_email' => $this->maskEmail($user->email),
            'masked_phone' => $this->maskPhone($user->phone),
        ];
    }

    private function maskEmail(?string $email): ?string
    {
        if (!$email) return null;
        [$local, $domain] = explode('@', $email, 2);
        $head = mb_substr($local, 0, 1);
        $tail = mb_substr($local, -1);
        $stars = str_repeat('*', max(2, mb_strlen($local) - 2));
        return "{$head}{$stars}{$tail}@{$domain}";
    }

    private function maskPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $len = mb_strlen($phone);
        if ($len <= 4) return $phone;
        return mb_substr($phone, 0, 2) . str_repeat('*', $len - 4) . mb_substr($phone, -2);
    }
}
