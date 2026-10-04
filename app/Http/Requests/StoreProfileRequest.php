<?php

namespace App\Http\Requests;

class StoreProfileRequest extends ProfileRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'student_number' => $this->studentNumberRules(true),
            ...$this->commonProfileRules(),
        ];
    }
}
