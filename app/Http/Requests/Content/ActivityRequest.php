<?php

namespace App\Http\Requests\Content;

use App\Models\Activity;
use App\Rules\MaxWords;
use App\Services\ArticleContent;
use Illuminate\Validation\Rule;

class ActivityRequest extends ContentRequest
{
    protected string $modelClass = Activity::class;

    protected string $routeParameter = 'activity';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:20000', new MaxWords],
            'content_format' => app(ArticleContent::class)->availableFor('activities')
                ? ['sometimes', 'required', Rule::in(['plain', 'markdown'])] : ['exclude'],
            'activity_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,png', 'max:2048'],
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
            'image.mimes' => 'The image must be a JPG or PNG file.',
            'image.extensions' => 'The image must have a .jpg or .png extension.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ];
    }
}
