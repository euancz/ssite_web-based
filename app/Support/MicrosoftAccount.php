<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class MicrosoftAccount
{
    public const PENDING_SESSION_KEY = 'pending_microsoft_account';

    public static function find(string $id, string $email): ?User
    {
        $user = User::where('microsoft_id', $id)->first() ?? User::where('email', $email)->first();

        if ($user?->microsoft_id && $user->microsoft_id !== $id) {
            throw ValidationException::withMessages([
                'account' => 'This email address is already linked to a different Microsoft account.',
            ]);
        }

        return $user;
    }
}
