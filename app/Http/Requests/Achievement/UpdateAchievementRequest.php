<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

/** Validates achievement fields and an optional replacement image. */
class UpdateAchievementRequest extends FormRequest
{
    /** The controller applies AchievementPolicy before saving changes. */
    public function authorize(): bool
    {
        return true;
    }

    /** Accept only editable achievement fields and a supported image upload. */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'awardee' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'achievement_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** Keep achievement validation messages clear and specific. */
    public function messages(): array
    {
        return [
            'title.required' => 'Enter an achievement title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'description.required' => 'Enter the achievement description.',
            'awardee.max' => 'The awardee may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'achievement_date.date' => 'Enter a valid achievement date.',
            'image.image' => 'Choose a valid image file.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ];
    }
}
