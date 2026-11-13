<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Support\MobileLogin;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

/*
 * Microsoft login flow: Socialite verifies the identity, then the callback finds or creates
 * the local user, sends incomplete profiles to completion, and sends completed accounts to the CMS home.
 */
/**
 * Starts Microsoft sign-in and connects the verified Microsoft identity to a local user.
 */
class MicrosoftAuthController extends Controller
{
    /**
     * Start the Microsoft OAuth flow and request an interactive account selection.
     */
    public function redirect(Request $request)
    {
        if ($redirect = $this->redirectToCallbackHost($request)) {
            return $redirect;
        }

        return $this->startMicrosoftSignIn();
    }

    public function redirectForMobile(Request $request)
    {
        $returnUrl = $request->query('return_url');
        if ($returnUrl !== null) {
            abort_unless(MobileLogin::allowsWebReturnUrl($returnUrl), 422, 'The app return address is not allowed.');
        }
        if ($redirect = $this->redirectToCallbackHost($request, true)) {
            return $redirect;
        }

        $request->session()->put('mobile_login_return_url', $returnUrl);
        $request->session()->put('mobile_login', true);

        if (Auth::check()) {
            $user = $request->user();
            if (! $user->hasCompletedProfile()) {
                return redirect()->route('profile.complete');
            }

            return MobileLogin::redirectToApp($request, $user);
        }

        return $this->startMicrosoftSignIn();
    }

    private function redirectToCallbackHost(Request $request, bool $mobile = false): ?\Illuminate\Http\RedirectResponse
    {
        $callback = (string) config('services.microsoft.redirect');
        if (! Str::startsWith($callback, ['http://', 'https://'])
            || ! Str::endsWith($callback, '/auth/microsoft/callback')) {
            return null;
        }

        // Establish the session on the callback host before storing OAuth state or app return data.
        // localhost and 127.0.0.1 do not share browser cookies.
        $target = Str::beforeLast($callback, '/callback') . ($mobile ? '/mobile' : '');
        if ($request->url() === $target) {
            return null;
        }

        if ($mobile && $request->query('return_url') !== null) {
            $target .= '?' . http_build_query(['return_url' => $request->query('return_url')]);
        }

        return redirect()->away($target);
    }

    private function startMicrosoftSignIn()
    {
        return Socialite::driver('microsoft')
            ->with([
                'prompt' => 'login',
            ])
            ->redirect();
    }

    /**
     * Validate Microsoft's identity, preserve existing profile edits, and route after login.
     */
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

            // Prefer the stable Microsoft ID; email is used only to link a prior local account.
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
                // Set system-managed fields directly so they never need to be mass assignable.
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->microsoft_id = $microsoftId;
                $user->password = Hash::make(Str::random(64));
                $user->profile_picture = null;
                $user->role = 'student';
            }

            // Leave existing pictures untouched; this callback does not fetch Microsoft photos.

            if ($user->isDirty()) {
                $user->save();
            }

            Auth::login($user);

            request()->session()->regenerate();

            if ($user->hasCompletedProfile()) {
                $mobileRedirect = MobileLogin::redirectToApp(request(), $user);
                if ($mobileRedirect) {
                    return $mobileRedirect;
                }
            }

            // Profile enforcement happens before role routing so unfinished accounts cannot browse.
            if (! $user->hasCompletedProfile()) {
                return redirect()->route('profile.complete');
            }

            return redirect()->route('home');

        } catch (InvalidStateException $e) {
            report($e);

            $message = 'Microsoft could not verify this sign-in session. Please start again from this site and use the same browser tab throughout sign-in.';
            if (config('app.debug')) {
                $message .= ' Details: ' . $e->getMessage();
            }

            return redirect('/')->with('error', $message);
        } catch (\Throwable $e) {
            report($e);

            $message = 'Microsoft sign-in could not be completed. Please try again.';
            if (config('app.debug')) {
                $message .= ' Details: ' . $e->getMessage();
            }

            return redirect('/')->with('error', $message);
        }
    }
}
