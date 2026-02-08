<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'token' => [
                'description' => 'The token received from the sendOtp endpoint.',
                'example' => '8a7b3c...',
            ],
            'otp' => [
                'description' => 'The 6-digit code received via SMS/Email.',
                'example' => '123456',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز التحقق مطلوب',
            'otp.required' => 'رمز OTP مطلوب',
            'otp.size' => 'رمز OTP يجب أن يكون 6 أرقام',
        ];
    }
}
