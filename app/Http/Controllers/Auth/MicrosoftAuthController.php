<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\MicrosoftAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Support\MobileLogin;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        $request->session()->forget(['mobile_login', 'mobile_login_return_url', 'mobile_login_challenge']);

        return $this->startMicrosoftSignIn();
    }

    public function redirectForMobile(Request $request)
    {
        $challenge = $request->query('code_challenge');
        abort_unless($challenge === null || (is_string($challenge) && preg_match('/\A[0-9a-f]{64}\z/', $challenge)), 422);
        $returnUrl = $request->query('return_url');
        if ($returnUrl !== null) {
            abort_unless(MobileLogin::allowsWebReturnUrl($returnUrl), 422, 'The app return address is not allowed.');
        }
        if ($redirect = $this->redirectToCallbackHost($request, true)) {
            return $redirect;
        }

        $request->session()->put('mobile_login_return_url', $returnUrl);
        $request->session()->put('mobile_login_challenge', $challenge);
        $request->session()->put('mobile_login', true);

        if ($challenge !== null) {
            // Let the app user choose a Microsoft account independently of the browser's current account.
            if (Auth::check()) {
                Auth::logout();
                $request->session()->regenerate(true);
            }

            return $this->startMicrosoftSignIn();
        }

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

        if ($mobile) {
            $params = array_filter($request->only(['return_url', 'code_challenge']), fn ($value) => $value !== null);
            if ($params) {
                $target .= '?'.http_build_query($params);
            }
        }

        return redirect()->away($target);
    }

    private function startMicrosoftSignIn()
    {
        request()->session()->forget(MicrosoftAccount::PENDING_SESSION_KEY);

        return Socialite::driver('microsoft')
            ->with([
                'prompt' => 'login',
            ])
            ->redirect();
    }

    /**
     * Validate Microsoft's identity, preserve existing profile edits, and route after login.
     */
    public function callback(Request $request)
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            $email = strtolower((string) $microsoftUser->getEmail());
            $microsoftId = (string) $microsoftUser->getId();
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

            $user = MicrosoftAccount::find($microsoftId, $email);
            if (! $user?->hasCompletedProfile()) {
                $request->session()->regenerate(true);
                $request->session()->put(MicrosoftAccount::PENDING_SESSION_KEY, [
                    'id' => $microsoftId,
                    'email' => $email,
                    'name' => $name,
                ]);

                return redirect()->route('profile.complete');
            }

            if (! $user->microsoft_id) {
                $user->newQuery()->whereKey($user->getKey())->whereNull('microsoft_id')
                    ->update(['microsoft_id' => $microsoftId]);
                if ($user->refresh()->microsoft_id !== $microsoftId) {
                    return redirect()->route('landing')
                        ->with('error', 'This email address is already linked to a different Microsoft account.');
                }
            }

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(MicrosoftAccount::PENDING_SESSION_KEY);

            if ($mobileRedirect = MobileLogin::redirectToApp($request, $user)) {
                return $mobileRedirect;
            }

            // Consume this once on the first CMS page after successful authentication.
            $request->session()->put('show_login_success', true);

            return redirect()->route('home');

        } catch (ValidationException $e) {
            return redirect()->route('landing')->with('error', $e->validator->errors()->first());
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
