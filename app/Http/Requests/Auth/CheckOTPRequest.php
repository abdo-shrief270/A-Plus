<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CheckOTPRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'string',
            'otp' => 'required|string|min:4|max:4',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'الكود مطلوب.',
            'otp.string' => 'رمز التحقق يجب أن يكون نص.',
            'otp.min' => 'رمز التحقق يجب أن يكون 4 أرقام.',
            'otp.max' => 'رمز التحقق يجب أن يكون 4 أرقام.',

            'token.string' => 'التوكين لازم يكون نص.',


        ];
    }
}
