<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

/** Validates document fields and the required PDF before controller-side content checks. */
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:' . config('school.max_pdf_size_kb', 10240)],
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'Enter a document title.', 'title.max' => 'The title may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'file.required' => 'Choose a PDF to upload.', 'file.mimes' => 'Choose a valid PDF document.',
            'file.max' => 'The PDF exceeds the configured upload size limit.',
        ];
    }
}
