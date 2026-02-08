<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'token' => [
                'description' => 'The verification token received after verifying OTP.',
                'example' => '8a7b3c...',
            ],
            'password' => [
                'description' => 'The new password (min 8 characters).',
                'example' => 'newpassword123',
            ],
            'password_confirmation' => [
                'description' => 'Confirm the new password.',
                'example' => 'newpassword123',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز التحقق مطلوب',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ];
    }
}
