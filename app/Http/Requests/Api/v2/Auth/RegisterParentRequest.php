<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'user_name' => ['nullable', 'string', 'min:3', 'max:255', 'unique:users,user_name', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required_with:phone', 'string', 'max:5'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'gender' => ['required', 'in:male,female'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.min' => 'الاسم يجب أن يكون حرفين على الأقل',
//            'user_name.required' => 'اسم المستخدم مطلوب',
            'user_name.unique' => 'اسم المستخدم مستخدم بالفعل',
            'user_name.regex' => 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام فقط',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'gender.required' => 'الجنس مطلوب',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
        ];
    }
}
