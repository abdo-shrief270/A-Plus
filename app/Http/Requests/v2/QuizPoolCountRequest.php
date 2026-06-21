<?php

namespace App\Http\Requests\v2;

use Illuminate\Foundation\Http\FormRequest;

class QuizPoolCountRequest extends FormRequest
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
            'source' => ['required', 'in:random,unanswered,wrong,bookmarked'],
            // Per-item exists intentionally omitted — see QuizStoreRequest.
            'section_ids' => ['nullable', 'array', 'max:50'],
            'section_ids.*' => ['integer', 'min:1'],
            'category_ids' => ['nullable', 'array', 'max:100'],
            'category_ids.*' => ['integer', 'min:1'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
        ];
    }
}
