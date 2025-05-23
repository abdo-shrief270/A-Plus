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
            'user_name' => 'string|exists:users,user_name',
            'phone' => 'string|exists:users,phone',
            'email' => 'string|email|exists:users,email',
            'country_code' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
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

            'country_code.string' => 'كود الدولة لازم يكون نص.',

            'email.string' => 'الإيميل لازم يكون نص.',
            'email.email' => 'الإيميل غير صحيح، تأكد من كتابته.',
            'email.exists' => 'الإيميل غير مستخدم من قبل.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور لازم تكون نص.',
            'password.min' => 'كلمة المرور لازم تكون ٦ حروف على الأقل.',
            'password.max' => 'كلمة المرور طويل مره، خففه شوي.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('user_name') && !$this->filled('phone') && !$this->filled('email')) {
                $validator->errors()->add('contact', 'يجب إدخال طريقة تواصل واحدة على الأقل.');
            }
        });
    }
}
