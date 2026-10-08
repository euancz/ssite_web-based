<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

/** Validates activity fields and an optional replacement image. */
class UpdateActivityRequest extends FormRequest
{
    /** The controller applies ActivityPolicy before saving changes. */
    public function authorize(): bool
    {
        return true;
    }

    /** Accept only editable activity fields and a supported image upload. */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'activity_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** Keep activity validation messages clear and specific. */
    public function messages(): array
    {
        return [
            'title.required' => 'Enter an activity title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'description.required' => 'Enter the activity description.',
            'activity_date.date' => 'Enter a valid activity date.',
            'location.max' => 'The location may not be longer than 200 characters.',
            'image.image' => 'Choose a valid image file.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ];
    }
}
