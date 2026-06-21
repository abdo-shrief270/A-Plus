<?php

namespace App\Http\Requests\v2;

use Illuminate\Foundation\Http\FormRequest;

class QuizAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_id' => 'السؤال',
            'answer_id' => 'الإجابة المختارة',
        ];
    }
}
