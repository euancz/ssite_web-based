<?php

namespace App\Http\Requests\Liquidation;

use Illuminate\Foundation\Http\FormRequest;

/** Requires an adviser rejection reason that fits the schema column. */
class RejectLiquidationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['rejection_reason' => ['required', 'string', 'max:255']]; }
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Enter a reason before rejecting this liquidation report.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ];
    }
}
