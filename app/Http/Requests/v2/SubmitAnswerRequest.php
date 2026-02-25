<?php

namespace App\Http\Requests\v2;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'exists:questions,id'],
            'answer_id' => ['nullable', 'exists:answers,id'],
            'user_answer' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question_id' => __('validation.attributes.question_id', [], 'ar') ?? 'السؤال',
            'answer_id' => __('validation.attributes.answer_id', [], 'ar') ?? 'الإجابة المختارة',
            'user_answer' => __('validation.attributes.user_answer', [], 'ar') ?? 'إجابة المستخدم',
        ];
    }
}
