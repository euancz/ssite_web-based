<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

/** Requires an adviser explanation before an activity can be rejected. */
class RejectActivityRequest extends FormRequest
{
    /** The adviser-only route and controller policy authorize review access. */
    public function authorize(): bool
    {
        return true;
    }

    /** Accept only the rejection reason from the review dialog. */
    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:255']];
    }

    /** Explain the required reason and its database limit. */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Enter a reason before rejecting this activity.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ];
    }
}
