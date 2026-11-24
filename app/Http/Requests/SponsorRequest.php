<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-sponsors') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'logo' => ['bail', 'nullable', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'remove_logo' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image' => 'Upload a JPG, JPEG, or PNG image.',
            'logo.mimes' => 'Upload a JPG, JPEG, or PNG image.',
            'logo.extensions' => 'Use a .jpg, .jpeg, or .png file extension for the sponsor logo.',
        ];
    }
}
