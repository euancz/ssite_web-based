<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = json_decode(Crypt::decryptString((string) $request->bearerToken()), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userId = filter_var($payload['sub'] ?? null, FILTER_VALIDATE_INT);
        $tokenId = $payload['jti'] ?? null;
        if (! $userId || ! is_string($tokenId) || ($payload['exp'] ?? 0) < time()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ((int) Cache::get('mobile_access:' . $tokenId) !== $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find($userId);
        if (! $user || ! $user->hasCompletedProfile()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('mobile_user', $user);
        $request->attributes->set('mobile_token_id', $tokenId);

        return $next($request);
    }
}