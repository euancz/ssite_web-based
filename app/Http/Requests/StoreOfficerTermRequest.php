<?php

namespace App\Http\Requests;

use App\Support\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate adviser-submitted officer snapshots before any term or image is stored. */
class StoreOfficerTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdviser() === true;
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/', function ($attribute, $value, $fail): void {
                if (! AcademicYear::isValid($value)) $fail('Choose a valid consecutive academic year.');
            }],
            'position' => ['required', 'string', 'max:100', Rule::in(config('school.officer_positions', []))],
            'name' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.regex' => 'Choose an academic year in YYYY-YYYY format.',
            'position.in' => 'Choose a position from the approved list.',
            'user_id.exists' => 'The selected user account could not be found.',
            'photo.image' => 'Upload a JPG, PNG, or WebP image.',
            'photo.max' => 'The officer photo must be 2 MB or smaller.',
        ];
    }
}
