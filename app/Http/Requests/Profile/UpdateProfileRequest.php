<?php

namespace App\Http\Requests\Profile;

/**
 * Validates later profile edits while keeping Microsoft-managed identity fields read-only.
 */
class UpdateProfileRequest extends ProfileRequest
{
    /**
     * Restrict updates to the authenticated account rather than a request-supplied user ID.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Allow users to update their own student number while rejecting submitted Microsoft identity changes.
     */
    public function rules(): array
    {
        return [
            'name' => ['prohibited'],
            'student_number' => $this->studentNumberRules(false),
            ...$this->commonProfileRules(),
        ];
    }
}
