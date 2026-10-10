<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

/** Requires the adviser to provide a database-safe rejection reason. */
class RejectDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['rejection_reason' => ['required', 'string', 'max:255']]; }
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Enter a reason before rejecting this document.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ];
    }
}
