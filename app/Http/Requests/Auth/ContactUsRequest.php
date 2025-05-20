<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ContactUsRequest extends FormRequest
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
            'email' => 'string|email|max:255',
            'description' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يجب كتابة الاسم كامل.',
            'name.string' => 'الاسم لازم يكون نص.',
            'name.max' => 'الاسم ما يتعدى ٢٥٥ حرف.',

            'email.string' => 'الإيميل لازم يكون نص.',
            'email.email' => 'الإيميل غير صحيح، تأكد من كتابته.',
            'email.max' => 'الإيميل طويل مره، حاول تختصر.',

            'description.required' => 'الوصف مطلوب.',
            'description.string' => 'الوصف لازم تكون نص.',
        ];
    }

}
