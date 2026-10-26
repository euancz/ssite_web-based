<?php

namespace App\Http\Requests\Content;

use App\Models\Article;
use App\Rules\MaxWords;

class ArticleRequest extends ContentRequest
{
    protected string $modelClass = Article::class;

    protected string $routeParameter = 'article';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', new MaxWords],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Enter an article title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'content.required' => 'Enter the article content.',
            'image.image' => 'Choose a valid image file.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ];
    }
}
