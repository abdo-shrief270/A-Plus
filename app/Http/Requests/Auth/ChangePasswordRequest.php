<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'password' => 'required|string|min:6|confirmed',
        ];
    }
    public function messages(): array
    {
        return [
            'token.string' => 'التوكين لازم يكون نص.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور لازم تكون نص.',
            'password.min' => 'كلمة المرور لازم تكون ٦ حروف على الأقل.',
            'password.max' => 'كلمة المرور طويل مره، خففه شوي.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }
}
