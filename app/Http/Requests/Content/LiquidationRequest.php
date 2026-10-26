<?php

namespace App\Http\Requests\Content;

use App\Models\Liquidation;
use App\Rules\MaxWords;

class LiquidationRequest extends ContentRequest
{
    protected string $modelClass = Liquidation::class;

    protected string $routeParameter = 'liquidation';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', new MaxWords],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'report_date' => ['nullable', 'date'],
            'file' => [$this->route('liquidation') ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:'.config('school.max_pdf_size_kb', 10240)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Enter a liquidation report title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'amount.numeric' => 'Enter a valid amount.',
            'amount.min' => 'The amount may not be negative.',
            'amount.max' => 'The amount exceeds the supported limit.',
            'report_date.date' => 'Enter a valid report date.',
            'file.required' => 'Choose a PDF to upload.',
            'file.mimes' => 'Choose a valid PDF document.',
            'file.max' => 'The PDF exceeds the configured upload size limit.',
        ];
    }
}
