<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobileWorkspace
{
    public static function allowed(User $user): bool
    {
        return $user->hasCompletedProfile() && ($user->canPost() || $user->canReview()
            || $user->can('manage-users') || $user->can('manage-officers'));
    }

    public static function issue(User $user, string $tokenId): string
    {
        abort_unless(self::allowed($user), 403);
        $code = Str::random(64);
        Cache::put('mobile_workspace:' . hash('sha256', $code), [
            'user_id' => $user->getKey(), 'token_id' => $tokenId,
        ], now()->addMinute());
        return route('mobile.workspace.open', ['code' => $code]);
    }

    public static function consume(string $code): User
    {
        $session = Cache::pull('mobile_workspace:' . hash('sha256', $code));
        abort_unless(is_array($session), 401, 'Workspace link expired. Reopen it from the app.');
        $user = User::find($session['user_id']);
        abort_unless($user && (int) Cache::get('mobile_access:' . $session['token_id']) === (int) $user->getKey(), 401);
        abort_unless(self::allowed($user), 403);
        return $user;
    }
}