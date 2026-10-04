<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || $user->hasCompletedProfile()
            || ($user->isAdviser() && ! config('school.require_profile_for_all_roles', true))
        ) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Complete your profile before continuing.',
                'profile_url' => route('profile.complete'),
            ], Response::HTTP_FORBIDDEN);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('profile.complete');
    }
}
