<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => 'string|max:255',

            'user_name' => [
                'string',
                'min:5',
                'max:255',
                Rule::unique('users', 'user_name')->ignore($this->user()->id),
            ],

            'phone' => [
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],

            'email' => [
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            'exam_date' => 'nullable|date|after:today',

            'id_number' => [
                'string',
                'max:255',
                Rule::unique('students', 'id_number')->ignore($this->user()->id),
            ],

            'old_password' => 'required_with:password|string|min:6',

            'password' => 'nullable|string|min:6|confirmed',
        ];
    }


    public function messages(): array
    {
        return [
            'name.string' => 'الاسم لازم يكون نص.',
            'name.max' => 'الاسم طويل مره، خففه شوي.',

            'user_name.string' => 'اسم المستخدم لازم يكون نص.',
            'user_name.min' => 'اسم المستخدم لازم يكون ٦ حروف على الأقل.',
            'user_name.max' => 'اسم المستخدم طويل مره، خففه شوي.',
            'user_name.unique' => 'اسم المستخدم هذا مستخدم من قبل.',

            'phone.string' => 'رقم الجوال لازم يكون نص.',
            'phone.max' => 'رقم الجوال طويل، تأكد منه.',
            'phone.unique' => 'الرقم هذا مسجل من قبل.',

            'email.string' => 'الإيميل لازم يكون نص.',
            'email.email' => 'الإيميل غير صحيح، تأكد من كتابته.',
            'email.max' => 'الإيميل طويل مره، حاول تختصر.',
            'email.unique' => 'الإيميل هذا مستخدم من قبل.',

            'exam_date.date' => 'تاريخ الاختبار غير صحيح.',
            'exam_date.after' => 'لازم تاريخ الاختبار يكون بعد اليوم.',

            'id_number.string' => 'رقم الهوية لازم يكون نص.',
            'id_number.max' => 'رقم الهوية طويل مره.',
            'id_number.unique' => 'رقم الهوية هذا مسجل من قبل.',

            'old_password.required' => 'يجب إدخال كلمة المرور القديمة عند تغيير كلمة المرور.',
            'old_password.string' => 'كلمة المرور القديمة يجب أن تكون نص.',
            'old_password.min' => 'كلمة المرور القديمة يجب أن تكون ٦ حروف على الأقل.',
            'old_password.required_with' => 'يجب إدخال كلمة المرور القديمة عند تغيير كلمة المرور.',

            'password.string' => 'كلمة المرور يجب أن تكون نص.',
            'password.min' => 'كلمة المرور يجب أن تكون ٦ حروف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }
}
