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
            'otpMethod' => 'required|in:phone,email,whatsapp',

            'user_name' => 'nullable|string|exists:users,user_name',

            'phone' => 'required_if:otpMethod,phone|string|exists:users,phone',
            'whatsapp' => 'required_if:otpMethod,whatsapp|string|exists:users,phone',
            'email' => 'required_if:otpMethod,email|string|email|exists:users,email',

            'country_code' => 'nullable|string',

            'otp' => 'required|string|min:4|max:4',
        ];
    }

    public function messages(): array
    {
        return [
            'otpMethod.required' => 'طريقة إرسال الكود مطلوبة.',
            'otpMethod.in' => 'طريقة الكود يجب أن تكون إما الجوال أو الإيميل أو الواتساب.',

            'otp.required' => 'الكود مطلوب.',

            'otp.string' => 'رمز التحقق يجب أن يكون نص.',
            'otp.min' => 'رمز التحقق يجب أن يكون 4 أرقام.',
            'otp.max' => 'رمز التحقق يجب أن يكون 4 أرقام.',

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

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('user_name') && !$this->filled('phone') && !$this->filled('whatsapp') && !$this->filled('email')) {
                $validator->errors()->add('contact', 'يجب إدخال طريقة تواصل واحدة على الأقل.');
            }
        });
    }
}
