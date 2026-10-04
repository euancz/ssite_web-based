<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('microsoft')
            ->with([
                'prompt' => 'login',
            ])
            ->redirect();
    }

    public function callback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            $email = strtolower((string) $microsoftUser->getEmail());
            $microsoftId = $microsoftUser->getId();
            $name = $microsoftUser->getName()
                ?: $microsoftUser->getNickname()
                ?: 'Student';
            $emailDomain = strtolower((string) config('school.microsoft_email_domain', ''));

            if (
                !$email ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                ($emailDomain !== '' && Str::afterLast($email, '@') !== $emailDomain)
            ) {
                return redirect('/')
                    ->with(
                        'error',
                        $emailDomain !== ''
                            ? "Only {$emailDomain} Microsoft accounts are allowed."
                            : 'Microsoft did not provide a valid email address.'
                    );
            }

            if (! $microsoftId) {
                return redirect('/')
                    ->with('error', 'Microsoft did not provide a valid account identifier.');
            }

            $user = User::where('microsoft_id', $microsoftId)->first();

            if (!$user) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    if ($user->microsoft_id && $user->microsoft_id !== $microsoftId) {
                        return redirect('/')
                            ->with('error', 'This email address is already linked to a different Microsoft account.');
                    }

                    if (! $user->microsoft_id) {
                        $user->microsoft_id = $microsoftId;
                    }
                }
            }

            if (!$user) {
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->microsoft_id = $microsoftId;
                $user->password = Hash::make(Str::random(64));
                $user->profile_picture = config('school.default_profile_picture', 'images/Wolf.png');
                $user->role = 'student';
            }

            if (! $user->profile_picture) {
                $user->profile_picture = config('school.default_profile_picture', 'images/Wolf.png');
            }

            if ($user->isDirty()) {
                $user->save();
            }

            Auth::login($user);

            request()->session()->regenerate();

            if (! $user->hasCompletedProfile()) {
                return redirect()->route('profile.complete');
            }

            return redirect()->intended(route($user->dashboardRoute()));

        } catch (InvalidStateException $e) {
            report($e);

            return redirect('/')
                ->with(
                    'error',
                    'Microsoft could not verify this sign-in session. Please start again from this site and use the same address throughout sign-in.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect('/')
                ->with(
                    'error',
                    'Microsoft sign-in could not be completed. Please try again.'
                );
        }
    }
}