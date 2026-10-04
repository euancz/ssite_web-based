<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps authenticated users on profile completion until their required information is saved.
 *
 * Completion and logout routes are deliberately excluded from this middleware; JSON requests
 * receive a forbidden response, while browser requests return to the completion form.
 */
class EnsureProfileCompleted
{
    /**
     * Allow complete profiles through and redirect incomplete users to their own form.
     */
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
