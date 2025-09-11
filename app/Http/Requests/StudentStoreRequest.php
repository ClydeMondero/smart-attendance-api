<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:50', 'unique:students,barcode'],
            'full_name' => ['required', 'string', 'max:191'],
            'grade_level' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'parent_contact' => ['nullable', 'string', 'max:20'],
        ];
    }
}
