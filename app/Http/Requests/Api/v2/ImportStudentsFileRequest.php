<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsFileRequest extends FormRequest
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
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120', // 5MB max
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for import',
            'file.mimes' => 'File must be a CSV or Excel file',
            'file.max' => 'File size must not exceed 5MB',
        ];
    }
}
