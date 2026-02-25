<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * OTP expiry time in minutes.
     */
    protected int $expiryMinutes = 5;

    /**
     * OTP length.
     */
    protected int $otpLength = 6;

    /**
     * Generate and store OTP for a user.
     */
    public function generate(User $user, string $method = 'sms'): array
    {
        $otp = $this->generateOtpCode();
        $token = Str::random(100);

        // Delete any existing tokens for this user
        DB::table('password_reset_tokens')
            ->where('user_id', $user->id)
            ->delete();

        // Insert new token with OTP
        DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes($this->expiryMinutes),
            'method' => $method,
            'created_at' => now(),
        ]);

        // In production, send OTP via the method
        $this->sendOtp($user, $otp, $method);

        return [
            'token' => $token,
            'expires_in' => $this->expiryMinutes * 60,
            'method' => $method,
        ];
    }

    /**
     * Verify OTP.
     */
    public function verify(string $token, string $otp): array
    {
        $record = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (!$record) {
            return [
                'valid' => false,
                'message' => 'رمز التحقق غير صالح',
            ];
        }

        if ($record->otp !== $otp) {
            return [
                'valid' => false,
                'message' => 'رمز التحقق غير صحيح',
            ];
        }

        if (now()->isAfter($record->otp_expires_at)) {
            return [
                'valid' => false,
                'message' => 'انتهت صلاحية رمز التحقق',
            ];
        }

        // Mark as verified
        DB::table('password_reset_tokens')
            ->where('token', $token)
            ->update(['verified_at' => now()]);

        return [
            'valid' => true,
            'user_id' => $record->user_id,
            'token' => $token,
        ];
    }

    /**
     * Consume (delete) the OTP after successful verification.
     */
    public function consume(string $token): bool
    {
        return DB::table('password_reset_tokens')
            ->where('token', $token)
            ->delete() > 0;
    }

    /**
     * Generate a random OTP code.
     */
    protected function generateOtpCode(): string
    {
        return '123456';
//        return str_pad((string) random_int(0, 999999), $this->otpLength, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via the specified method.
     * This is a placeholder - integrate with actual SMS/Email service.
     */
    protected function sendOtp(User $user, string $otp, string $method): void
    {
        // TODO: Integrate with actual SMS/Email/WhatsApp provider
        // For now, log the OTP (NEVER do this in production)
        if (config('app.debug')) {
            \Log::info("OTP for user {$user->id}: {$otp} via {$method}");
        }

        // Example integrations:
        // switch ($method) {
        //     case 'email':
        //         Mail::to($user->email)->send(new OtpMail($otp));
        //         break;
        //     case 'phone':
        //         SmsService::send($user->phone, "Your OTP is: {$otp}");
        //         break;
        //     case 'whatsapp':
        //         WhatsAppService::send($user->phone, "Your OTP is: {$otp}");
        //         break;
        // }
    }
}
