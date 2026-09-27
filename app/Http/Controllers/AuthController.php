<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        return view('auth.login');
    }

    // Process login
    public function login(Request $request)
    {
        // Validate input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Only allow MCC emails
        if (!str_ends_with(strtolower($credentials['email']), '@mcc.edu.ph')) {
            return back()->withErrors([
                'email' => 'Please use your MCC email address.',
            ])->withInput();
        }

        // Attempt login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            // Regenerate session after successful login
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        // Incorrect credentials
        return back()->withErrors([
            'email' => 'The email or password is incorrect.',
        ])->withInput();
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}