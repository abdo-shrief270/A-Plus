<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'اسم المستخدم مطلوب.',
            'user_name.string' => 'اسم المستخدم لازم يكون نص.',
            'user_name.max' => 'اسم المستخدم طويل مره، خففه شوي.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور لازم تكون نص.',
            'password.max' => 'كلمة المرور طويل مره، خففه شوي.',
        ];
    }
}
