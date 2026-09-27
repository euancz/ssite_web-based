<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
                ?: 'MCC Student';

            // Only MCC accounts are allowed
            if (
                !$email ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !str_ends_with($email, '@mcc.edu.ph')
            ) {
                return redirect()
                    ->route('login')
                    ->with(
                        'error',
                        'Only MCC email accounts are allowed.'
                    );
            }

            // Find existing account using Microsoft ID
            $user = User::where('microsoft_id', $microsoftId)->first();

            // If Microsoft ID is not linked yet, find by email
            if (!$user) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->update([
                        'microsoft_id' => $microsoftId,
                    ]);
                }
            }

            // Create a new student account if it does not exist
            if (!$user) {
                $user = User::create([
                    'student_number' => 'PENDING-' . strtoupper(Str::random(8)),
                    'name' => $name,
                    'year_level' => 'Not Set',
                    'program' => 'Not Set',
                    'institute' => 'Not Set',
                    'gender' => 'Not Set',
                    'contact_number' => 'Not Set',
                    'email' => $email,
                    'microsoft_id' => $microsoftId,
                    'address' => 'Not Set',
                    'password' => bcrypt(Str::random(32)),
                    'remember_token' => null,
                    'profile_picture' => 'default.png',
                    'role' => 'student',
                    'officer_position' => null,
                ]);
            }

            // Log the user in
            Auth::login($user);

            request()->session()->regenerate();

            return redirect()->route('home');

        } catch (\Exception $e) {
    return redirect()
        ->route('login')
        ->with(
            'error',
            'Microsoft login error: ' . $e->getMessage()
        );
}
    }
}