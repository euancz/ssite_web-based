<?php

namespace App\Http\Requests\Profile;

/**
 * Validates the profile fields required to unlock the signed-in user's account.
 */
class StoreProfileRequest extends ProfileRequest
{
    /**
     * Allow only authenticated users to submit their own profile.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Require all completion fields and validate them against configured school options.
     */
    public function rules(): array
    {
        return [
            'student_number' => $this->studentNumberRules(true),
            ...$this->commonProfileRules(),
        ];
    }
}
