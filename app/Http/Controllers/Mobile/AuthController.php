<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MobileLogin;
use App\Support\MobileWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function exchange(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:64'],
            'code_verifier' => ['sometimes', 'required', 'string', 'regex:/\A[0-9a-f]{64}\z/'],
        ]);
        $challenge = isset($validated['code_verifier']) ? hash('sha256', $validated['code_verifier']) : null;
        $key = MobileLogin::codeKey($validated['code'], $challenge);
        $userId = Cache::lock($key.':claim', 5)->get(fn () => Cache::pull($key));
        $user = $userId ? User::find($userId) : null;
        abort_unless($user && $user->hasCompletedProfile(), 401, 'Sign-in code is invalid or expired.');

        $tokenId = Str::random(48);
        $expiresAt = now()->addDays(30);
        Cache::put('mobile_access:'.$tokenId, $user->getKey(), $expiresAt);
        $token = Crypt::encryptString(json_encode([
            'sub' => $user->getKey(),
            'jti' => $tokenId,
            'exp' => $expiresAt->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => ['name' => $user->name, 'email' => $user->email],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function me(Request $request)
    {
        $user = $request->attributes->get('mobile_user');

        return response()->json(['user' => [
            'name' => $user->name, 'email' => $user->email,
            'avatar_url' => $user->avatarUrl(),
            'role' => $user->role, 'position' => $user->position(),
            'can_access_workspace' => MobileWorkspace::allowed($user),
        ]])->header('Cache-Control', 'no-store, private');
    }

    public function logout(Request $request)
    {
        Cache::forget('mobile_access:'.$request->attributes->get('mobile_token_id'));

        return response()->json(['message' => 'Signed out.']);
    }
}
