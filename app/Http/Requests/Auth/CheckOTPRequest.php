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
            'user_name' => 'string|exists:users,user_name',
            'whatsapp' => 'string|exists:users,phone',
            'phone' => 'string|exists:users,phone',
            'email' => 'string|email|exists:users,email',
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.string' => 'اسم المستخدم لازم يكون نص.',
            'user_name.exists' => 'اسم المستخدم غير مستخدم من قبل.',

            'phone.string' => 'رقم الجوال لازم يكون نص.',
            'phone.exists' => 'رقم الجوال غير مسجل من قبل.',

            'whatsapp.string' => 'رقم الواتساب لازم يكون نص.',
            'whatsapp.exists' => 'رقم الواتساب غير مسجل من قبل.',

            'email.string' => 'الإيميل لازم يكون نص.',
            'email.email' => 'الإيميل غير صحيح، تأكد من كتابته.',
            'email.exists' => 'الإيميل غير مستخدم من قبل.',

        ];
    }
}
