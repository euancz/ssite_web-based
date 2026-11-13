<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** One-time access to account pages without granting workspace capabilities. */
class MobileAccountSession
{
    public const DESTINATIONS = ['profile' => 'profile.edit', 'notifications' => 'notifications.index', 'search' => 'search.index'];

    public static function issue(User $user, string $tokenId, string $destination): string
    {
        abort_unless($user->hasCompletedProfile() && isset(self::DESTINATIONS[$destination]), 403);
        $code = Str::random(64);
        Cache::put('mobile_account:'.hash('sha256', $code), [
            'user_id' => $user->getKey(), 'token_id' => $tokenId, 'destination' => $destination,
        ], now()->addMinute());
        return route('mobile.account.open', ['code' => $code]);
    }

    public static function consume(string $code): array
    {
        $session = Cache::pull('mobile_account:'.hash('sha256', $code));
        abort_unless(is_array($session), 401, 'Link expired. Reopen the page from the app.');
        $user = User::find($session['user_id']);
        abort_unless($user && (int) Cache::get('mobile_access:'.$session['token_id']) === (int) $user->getKey(), 401);
        abort_unless($user->hasCompletedProfile(), 403);
        return [$user, self::DESTINATIONS[$session['destination']]];
    }
}
