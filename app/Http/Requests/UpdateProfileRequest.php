<?php

namespace App\Http\Requests;

/**
 * Validates later profile edits while reserving student-number changes for advisers.
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
     * Keep students from changing student numbers while allowing advisers to correct them.
     */
    public function rules(): array
    {
        return [
            'student_number' => $this->user()->isAdviser()
                ? $this->studentNumberRules(false)
                : ['prohibited'],
            ...$this->commonProfileRules(),
        ];
    }
}
