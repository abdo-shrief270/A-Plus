<?php

namespace App\Http\Requests\Api\v2\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CheckUsernameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'اسم المستخدم مطلوب',
            'user_name.min' => 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل',
        ];
    }
}
