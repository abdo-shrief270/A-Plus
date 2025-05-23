<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Register extends FormRequest
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
            'name' => 'required|string|max:255',
            'user_name' => 'string|unique:users,user_name|min:5|max:255',
            'phone' => 'string|max:255|unique:users',
            'country_code' => 'nullable|string',
            'email' => 'string|email|max:255|unique:users',
            'exam_id' => 'exists:exams,id',
            'exam_date' => 'date|after:today',
            'id_number' => 'string|max:255|unique:students',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'required|in:student,parent',
            'gender' => 'required|in:male,female',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يجب كتابة الاسم كامل.',
            'name.string' => 'الاسم لازم يكون نص.',
            'name.max' => 'الاسم طويل مره، خففه شوي.',

            'user_name.required' => 'اسم المستخدم مطلوب.',
            'user_name.string' => 'اسم المستخدم لازم يكون نص.',
            'user_name.min' => 'اسم المستخدم لازم يكون ٦ حروف على الأقل.',
            'user_name.max' => 'اسم المستخدم طويل مره، خففه شوي.',
            'user_name.unique' => 'اسم المستخدم هذا مستخدم من قبل.',

            'phone.string' => 'رقم الجوال لازم يكون نص.',
            'phone.max' => 'رقم الجوال طويل، تأكد منه.',
            'phone.unique' => 'الرقم هذا مسجل من قبل.',

            'country_code' => 'nullable|string',

            'email.string' => 'الإيميل لازم يكون نص.',
            'email.email' => 'الإيميل غير صحيح، تأكد من كتابته.',
            'email.max' => 'الإيميل طويل مره، حاول تختصر.',
            'email.unique' => 'الإيميل هذا مستخدم من قبل.',

            'exam_id.required' => 'اختر الاختبار اللي بتسجل فيه.',
            'exam_id.exists' => 'الاختبار هذا مو موجود.',

            'exam_date.required' => 'حدد تاريخ الاختبار.',
            'exam_date.date' => 'تاريخ الاختبار غير صحيح.',
            'exam_date.after' => 'لازم تاريخ الاختبار يكون بعد اليوم.',

            'id_number.string' => 'رقم الهوية لازم يكون نص.',
            'id_number.max' => 'رقم الهوية طويل مره.',
            'id_number.unique' => 'رقم الهوية هذا مسجل من قبل.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور لازم تكون نص.',
            'password.min' => 'كلمة المرور لازم تكون ٦ حروف على الأقل.',
            'password.max' => 'كلمة المرور طويل مره، خففه شوي.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',

            'gender.required' => 'نوع المستخدم مطلوب.',
            'type.required' => 'نوع المستخدم مطلوب.',
        ];
    }

}
