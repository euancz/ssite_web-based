<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfilePictureRequest;
use App\Models\User;
use App\Services\ProfilePictures;
use App\Support\MicrosoftAccount;
use App\Support\MobileLogin;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $identity = $request->session()->get(MicrosoftAccount::PENDING_SESSION_KEY);

        if ($user?->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        return response()->view('profile.complete', [
            'user' => $user,
            'name' => $user?->name ?? ($identity['name'] ?? 'Member'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(UpdateProfilePictureRequest $request, ProfilePictures $pictures): RedirectResponse
    {
        $user = $request->user();
        $identity = $request->session()->get(MicrosoftAccount::PENDING_SESSION_KEY);

        if ($user?->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        if (! $user) {
            $user = MicrosoftAccount::find($identity['id'], $identity['email']) ?? (new User)->forceFill([
                'name' => $identity['name'],
                'email' => $identity['email'],
                'password' => Hash::make(Str::random(64)),
                'role' => User::ROLE_STUDENT,
            ]);
        }

        try {
            $user = $pictures->complete($user, $request->file('picture'), $identity['id'] ?? null);
        } catch (UniqueConstraintViolationException) {
            return redirect()->route('profile.complete')->withErrors([
                'account' => 'An account was created or linked while you were setting up your profile. Please try again.',
            ]);
        }

        if ($identity) {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(MicrosoftAccount::PENDING_SESSION_KEY);
            $request->session()->put('show_login_success', true);
        }

        if ($mobileRedirect = MobileLogin::redirectToApp($request, $user)) {
            return $mobileRedirect;
        }

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('status', 'Your profile is ready.');
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function updatePicture(UpdateProfilePictureRequest $request, ProfilePictures $pictures): RedirectResponse
    {
        $pictures->replace($request->user(), $request->file('picture'));

        return redirect()->route('profile.edit')->with('status', 'Your profile picture has been updated.');
    }

    public function removePicture(Request $request, ProfilePictures $pictures): RedirectResponse
    {
        $pictures->replace($request->user(), null);

        return redirect()->route('profile.edit')->with('status', 'Your profile picture has been removed.');
    }
}
