<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requires a short adviser explanation before an article can be rejected.
 */
class RejectArticleRequest extends FormRequest
{
    /**
     * Defer adviser access to ArticlePolicy in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept only the rejection reason from the review form.
     */
    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:255']];
    }

    /**
     * Explain the required reason and its database limit.
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Enter a reason before rejecting this article.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ];
    }
}
