<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateStudentsRequest extends FormRequest
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
            'students' => 'required|array|min:1|max:100',
            'students.*.name' => 'required|string|max:255',
            'students.*.user_name' => 'required|string|max:255',
            'students.*.email' => 'nullable|email',
            'students.*.phone' => 'nullable|string|max:20',
            'students.*.gender' => 'nullable|in:male,female',
            'students.*.exam_id' => 'nullable|exists:exams,id',
            'students.*.exam_date' => 'nullable|date',
            'students.*.id_number' => 'nullable|string|max:50',
            'students.*.password' => 'nullable|string|min:6',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'students.required' => 'Students array is required',
            'students.min' => 'At least one student is required',
            'students.max' => 'Maximum 100 students can be imported at once',
        ];
    }
}
