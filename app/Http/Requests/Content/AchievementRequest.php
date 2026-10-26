<?php

namespace App\Http\Requests\Content;

use App\Models\Achievement;
use App\Rules\MaxWords;

class AchievementRequest extends ContentRequest
{
    protected string $modelClass = Achievement::class;

    protected string $routeParameter = 'achievement';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', new MaxWords],
            'awardee' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'achievement_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

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
