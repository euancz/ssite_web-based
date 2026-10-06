<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a picture upload for the authenticated user's own account.
 */
class UpdateProfilePictureRequest extends FormRequest
{
    /**
     * Only a signed-in user may upload a picture for their own account.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Enforce supported image formats, a 2 MB limit, and safe image dimensions.
     */
    public function rules(): array
    {
        return [
            'picture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    /**
     * Show clear feedback for common picture upload validation failures.
     */
    public function messages(): array
    {
        return [
            'picture.required' => 'Choose a profile picture to upload.',
            'picture.image' => 'The profile picture must be a valid image.',
            'picture.mimes' => 'Use a JPG, PNG, or WEBP image.',
            'picture.max' => 'The profile picture must be no larger than 2 MB.',
            'picture.dimensions' => 'The image dimensions must be 4000 by 4000 pixels or smaller.',
        ];
    }
}
