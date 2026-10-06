<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateProfilePictureRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Shows and saves the signed-in user's student information in the users table.
 */
class ProfileController extends Controller
{
    /**
     * Show the required completion form, or send an already-complete user home.
     */
    public function create(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        return view('profile.complete', $this->formData($user));
    }

    /**
     * Validate and save only the authenticated user's submitted profile fields.
     */
    public function store(StoreProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        $data = $request->validated();
        $data['contact_number'] = $this->normalizePhoneNumber($data['contact_number']);

        $user->fill($data);
        $user->profile_completed_at = now();
        $user->save();

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('status', 'Your profile has been completed.');
    }

    /**
     * Render the edit form for the current user's existing information.
     */
    public function edit(): View
    {
        return view('profile.edit', $this->formData(auth()->user()));
    }

    /**
     * Update the current user's allowed profile fields without accepting identity or role data.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $data['contact_number'] = $this->normalizePhoneNumber($data['contact_number']);

        $user->fill($data);
        $user->save();

        return redirect()->route('profile.edit')
            ->with('status', 'Your profile has been updated.');
    }

    /**
     * Store a validated picture for the signed-in user, then return to profile settings.
     */
    public function updatePicture(UpdateProfilePictureRequest $request): RedirectResponse
    {
        $user = $request->user();
        $upload = $request->file('picture');
        $filename = $user->user_id . '-' . Str::random(40) . '.' . $upload->extension();
        $path = $upload->storeAs('profile-pictures', $filename, 'public');

        // Delete only a prior local file after the replacement has been stored successfully.
        $oldPicture = (string) $user->profile_picture;
        if ($oldPicture !== '' && ! preg_match('/^https?:\/\//i', $oldPicture)) {
            Storage::disk('public')->delete($oldPicture);
        }

        $user->profile_picture = $path;
        $user->save();

        return back()->with('status', 'Your profile picture has been updated.');
    }

    /**
     * Remove the signed-in user's local picture and return to profile settings.
     */
    public function removePicture(): RedirectResponse
    {
        $user = auth()->user();
        $picture = (string) $user->profile_picture;

        if ($picture !== '' && ! preg_match('/^https?:\/\//i', $picture)) {
            Storage::disk('public')->delete($picture);
        }

        $user->profile_picture = null;
        $user->save();

        return back()->with('status', 'Your profile picture has been removed.');
    }

    /**
     * Convert the accepted local mobile format to the normalized +63 format for storage.
     */
    private function normalizePhoneNumber(string $number): string
    {
        return str_starts_with($number, '09')
            ? '+63' . substr($number, 1)
            : $number;
    }

    /**
     * Build the configured option lists shared by the completion and edit forms.
     */
    private function formData(User $user): array
    {
        $programsByInstitute = config('school.programs_by_institute', []);
        $programOptions = $programsByInstitute !== []
            ? collect($programsByInstitute)->flatten()->unique()->values()->all()
            : config('school.programs', []);

        return [
            'user' => $user,
            'institutes' => config('school.institutes', []),
            'programs' => $programOptions,
            'programsByInstitute' => $programsByInstitute,
            'yearLevels' => config('school.year_levels', []),
            'genders' => config('school.genders', []),
        ];
    }
}
