<?php

namespace App\Http\Requests\Liquidation;

use Illuminate\Foundation\Http\FormRequest;

/** Validates editable report details and an optional replacement PDF. */
class UpdateLiquidationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'], 'report_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:' . config('school.max_pdf_size_kb', 10240)],
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'Enter a liquidation report title.', 'title.max' => 'The title may not be longer than 200 characters.',
            'amount.numeric' => 'Enter a valid amount.', 'amount.min' => 'The amount may not be negative.',
            'amount.max' => 'The amount exceeds the supported limit.', 'report_date.date' => 'Enter a valid report date.',
            'file.mimes' => 'Choose a valid PDF document.', 'file.max' => 'The PDF exceeds the configured upload size limit.',
        ];
    }
}
