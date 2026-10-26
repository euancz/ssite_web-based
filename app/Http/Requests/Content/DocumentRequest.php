<?php

namespace App\Http\Requests\Content;

use App\Models\Document;
use App\Rules\MaxWords;
use Illuminate\Validation\Rule;

class DocumentRequest extends ContentRequest
{
    protected string $modelClass = Document::class;

    protected string $routeParameter = 'document';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', new MaxWords],
            'category' => ['nullable', 'string', 'max:100'],
            'semester' => ['nullable', Rule::in(['1', '2'])],
            'file' => [$this->route('document') ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:'.config('school.max_pdf_size_kb', 10240)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Enter a document title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'file.required' => 'Choose a PDF to upload.',
            'file.mimes' => 'Choose a valid PDF document.',
            'file.max' => 'The PDF exceeds the configured upload size limit.',
        ];
    }
}
