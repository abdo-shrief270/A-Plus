<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class resetPasswordRequest extends FormRequest
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
            'user_name' => 'string|exists:users,user_name',
            'whatsapp' => 'string|exists:users,phone',
            'phone' => 'string|exists:users,phone',
            'email' => 'string|email|exists:users,email',
            'country_code' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
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

    protected function failedValidation(Validator $validator)
    {
        // change 422 to whatever code you want, e.g. 400
        throw new HttpResponseException(
            response()->json([
                'status'  => 400,
                'message' => 'Validation Failed',
                'errors'  => $validator->errors(),
            ], 400)
        );
    }
}
