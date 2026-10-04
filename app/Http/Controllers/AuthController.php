<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Handles password sign-in and logout for accounts that also support Microsoft login.
 */
class AuthController extends Controller
{
    // Show login page
    /**
     * Render the standalone login view when it is requested directly.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    // Process login
    /**
     * Validate credentials, regenerate the session, then enforce profile and role routing.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return redirect('/')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $validator->validated();

        // Attempt login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            // Regenerate session after successful login
            $request->session()->regenerate();

            $user = Auth::user();

            // Profile completion must precede the normal role dashboard redirect.
            if ($user && ! $user->hasCompletedProfile()) {
                return redirect()->route('profile.complete');
            }

            return redirect()->intended(route($user?->dashboardRoute() ?? 'home'));
        }

        // Incorrect credentials
        return redirect('/')
            ->withErrors([
                'email' => 'The email or password is incorrect.',
            ])
            ->withInput($request->only('email'));
    }

    // Logout
    /**
     * End the current session; logout remains available even when profile completion is pending.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}