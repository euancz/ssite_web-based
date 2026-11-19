<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\MicrosoftAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Handles the landing redirect and logout for authenticated accounts. */
class AuthController extends Controller
{
    /** Redirect protected-route sign-in requests to the public Microsoft landing page. */
    public function redirectToHome(Request $request)
    {
        if ($request->session()->has(MicrosoftAccount::PENDING_SESSION_KEY)) {
            return redirect()->route('profile.complete');
        }

        return redirect('/')->with('error', 'Sign in first before proceeding.');
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
