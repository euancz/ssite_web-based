<?php

namespace App\Http\Requests\Content;

use App\Models\Activity;
use App\Rules\MaxWords;

class ActivityRequest extends ContentRequest
{
    protected string $modelClass = Activity::class;

    protected string $routeParameter = 'activity';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', new MaxWords],
            'activity_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

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
