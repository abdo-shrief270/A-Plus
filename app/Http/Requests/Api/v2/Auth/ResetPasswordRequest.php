<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required_without_all:email,phone', 'string'],
            'email' => ['required_without_all:user_name,phone', 'email'],
            'phone' => ['required_without_all:user_name,email', 'string'],
            'country_code' => ['required_with:phone', 'string', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required_without_all' => 'يجب إدخال اسم المستخدم أو البريد الإلكتروني أو رقم الهاتف',
        ];
    }
}
