<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CheckUserNameRequest extends FormRequest
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
            'user_name' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'اسم المستخدم مطلوب.',
            'user_name.string' => 'اسم المستخدم لازم يكون نص.',
            'user_name.max' => 'اسم المستخدم طويل مره، خففه شوي.',
        ];
    }
}
