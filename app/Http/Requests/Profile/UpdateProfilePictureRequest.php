<?php

namespace App\Http\Requests\Profile;

use App\Support\MicrosoftAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a picture upload for the authenticated user's own account.
 */
class UpdateProfilePictureRequest extends FormRequest
{
    /**
     * A verified Microsoft sign-in may upload during setup before the account exists.
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            || ($this->routeIs('profile.complete.store') && $this->session()->has(MicrosoftAccount::PENDING_SESSION_KEY));
    }

    /**
     * Enforce supported image formats, a 2 MB limit, and safe image dimensions.
     */
    public function rules(): array
    {
        $rules = [
            'picture' => ['bail', 'required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];

        if ($this->routeIs('profile.complete.store')) {
            $rules['accept_policies'] = ['accepted'];
        }

        return $rules;
    }

    protected function getRedirectUrl(): string
    {
        return route($this->routeIs('profile.complete.store') ? 'profile.complete' : 'profile.edit');
    }

    /**
     * Show clear feedback for common picture upload validation failures.
     */
    public function messages(): array
    {
        return [
            'picture.required' => 'Choose a profile picture to upload.',
            'picture.image' => 'The profile picture must be a valid image.',
            'picture.mimes' => 'Use a JPG or PNG image.',
            'picture.extensions' => 'Choose a file ending in .jpg, .jpeg, or .png.',
            'picture.max' => 'The profile picture must be no larger than 2 MB.',
            'picture.dimensions' => 'The image dimensions must be 4000 by 4000 pixels or smaller.',
            'accept_policies.accepted' => 'Agree to the Terms of Service and Privacy Policy to upload a profile photo.',
        ];
    }
}
