<?php

namespace App\Http\Requests;

class UpdateProfileRequest extends ProfileRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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
