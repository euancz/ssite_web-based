<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobileLogin
{
    public static function allowsWebReturnUrl(mixed $url): bool
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            return false;
        }
        // Flutter assigns a new browser port in local development.
        if (app()->environment('local') && in_array($parts['host'], ['localhost', '127.0.0.1', '[::1]'], true)) {
            return true;
        }
        $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        return in_array($origin, config('mobile.web_origins', []), true);
    }

    public static function redirectToApp(Request $request, User $user): ?RedirectResponse
    {
        if (! $request->session()->pull('mobile_login', false)) {
            return null;
        }

        $returnUrl = $request->session()->pull('mobile_login_return_url');
        if ($returnUrl !== null && ! self::allowsWebReturnUrl($returnUrl)) {
            abort(422, 'The app return address is not allowed.');
        }
        $code = Str::random(64);
        Cache::put('mobile_login_code:' . hash('sha256', $code), $user->getKey(), now()->addMinutes(2));

        return redirect()->away($returnUrl !== null
            ? $returnUrl . '#ssite_code=' . urlencode($code)
            : 'ssiteapp://auth/callback?code=' . urlencode($code));
    }
}