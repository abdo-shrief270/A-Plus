<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required_without_all:email,phone', 'string', 'exists:users,user_name'],
            'email' => ['required_without_all:user_name,phone', 'email', 'exists:users,email'],
            'phone' => ['required_without_all:user_name,email', 'string'],
            'country_code' => ['required_with:phone', 'string', 'max:5'],
            'method' => ['required', 'in:email,phone,whatsapp'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required_without_all' => 'يجب إدخال اسم المستخدم أو البريد الإلكتروني أو رقم الهاتف',
            'user_name.exists' => 'اسم المستخدم غير موجود',
            'email.exists' => 'البريد الإلكتروني غير موجود',
            'method.required' => 'طريقة الإرسال مطلوبة',
            'method.in' => 'طريقة الإرسال غير صالحة',
        ];
    }
}
