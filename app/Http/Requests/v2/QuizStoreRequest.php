<?php

namespace App\Http\Requests\v2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuizStoreRequest extends FormRequest
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
            'mode' => ['required', 'in:tutor,exam'],
            'source' => ['required', 'in:random,unanswered,wrong,bookmarked'],
            // No per-item exists checks: they cost one query per element
            // (DoS vector on array bombs) and QuizService::expandScope
            // already drops any id outside the student's exam.
            'section_ids' => ['nullable', 'array', 'max:50'],
            'section_ids.*' => ['integer', 'min:1'],
            'category_ids' => ['nullable', 'array', 'max:100'],
            'category_ids.*' => ['integer', 'min:1'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Scope is mandatory except for bookmarked quizzes, which may run
            // across the student's entire bookmark list.
            if ($this->input('source') !== 'bookmarked'
                && empty($this->input('section_ids'))
                && empty($this->input('category_ids'))) {
                $v->errors()->add('scope', 'يجب اختيار قسم أو تصنيف واحد على الأقل.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'mode' => 'وضع الاختبار',
            'source' => 'مصدر الأسئلة',
            'section_ids' => 'الأقسام',
            'category_ids' => 'التصنيفات',
            'question_count' => 'عدد الأسئلة',
            'time_limit_minutes' => 'المدة الزمنية',
            'difficulty' => 'مستوى الصعوبة',
        ];
    }
}
