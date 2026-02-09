<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth('api')->id();

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', "unique:users,email,{$userId}"],
            'user_name' => ['sometimes', 'string', 'max:20'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'country_code' => ['required_with:phone', 'string', 'max:5'],
            'gender' => ['sometimes', 'in:male,female'],
            // Student-specific fields
            'exam_id' => ['sometimes', 'exists:exams,id'],
            'exam_date' => ['sometimes', 'nullable', 'date'],
            // Password change (optional)
            'old_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'الاسم يجب أن يكون حرفين على الأقل',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'exam_id.exists' => 'نوع الاختبار غير موجود',
            'old_password.required_with' => 'كلمة المرور القديمة مطلوبة لتغيير كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ];
    }
}
