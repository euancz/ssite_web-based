<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shares the school-configured validation rules and messages for profile submissions.
 */
abstract class ProfileRequest extends FormRequest
{
    /**
     * Validate profile fields against the allowed school options and PH mobile format.
     */
    protected function commonProfileRules(): array
    {
        $institute = (string) $this->input('institute', '');
        $programsByInstitute = config('school.programs_by_institute', []);
        $programs = $programsByInstitute !== []
            ? ($programsByInstitute[$institute] ?? [])
            : config('school.programs', []);

        return [
            'name' => ['required', 'string', 'max:100'],
            'institute' => ['required', 'string', Rule::in(config('school.institutes', []))],
            'program' => ['required', 'string', Rule::in($programs)],
            'year_level' => ['required', 'string', Rule::in(config('school.year_levels', []))],
            'gender' => ['required', 'string', Rule::in(config('school.genders', []))],
            'contact_number' => ['required', 'string', 'regex:/^(?:09\d{9}|\+639\d{9})$/'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Validate a unique student number using user_id so the current row is ignored safely.
     */
    protected function studentNumberRules(bool $required): array
    {
        return [
            $required ? 'required' : 'sometimes',
            'string',
            'max:20',
            'regex:' . config('school.student_number_regex'),
            Rule::unique('users', 'student_number')->ignore($this->user()->user_id, 'user_id'),
        ];
    }

    /**
     * Replace generic validation text with guidance suitable for the student form.
     */
    public function messages(): array
    {
        return [
            'student_number.required' => 'Enter your student number.',
            'student_number.unique' => 'That student number is already registered.',
            'student_number.regex' => 'Enter a student number in the school-approved format.',
            'student_number.prohibited' => 'Only an adviser can change your student number.',
            'name.required' => 'Enter your name.',
            'institute.required' => 'Select your institute.',
            'institute.in' => 'Select an institute from the available options.',
            'program.required' => 'Select your program.',
            'program.in' => 'Select a program from the available options for your institute.',
            'year_level.required' => 'Select your year level.',
            'year_level.in' => 'Select a valid year level.',
            'gender.required' => 'Select your gender.',
            'gender.in' => 'Select a valid gender.',
            'contact_number.required' => 'Enter your mobile number.',
            'contact_number.regex' => 'Enter a Philippine mobile number such as 09XXXXXXXXX or +639XXXXXXXXX.',
            'address.required' => 'Enter your address.',
        ];
    }
}
