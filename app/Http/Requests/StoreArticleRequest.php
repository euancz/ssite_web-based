<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the editable content and optional image for a new article.
 */
class StoreArticleRequest extends FormRequest
{
    /**
     * Defer role access to ArticlePolicy in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept only article text and supported image uploads.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Keep article form errors clear and specific.
     */
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
