<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects guests to sign-in and remembers a requested page for later.
 */
class RequireAuthentication
{
    /**
     * Require an authenticated session before entering a protected route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            // Saving GET destinations lets users return to the page they requested after sign-in.
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect('/')->with('error', 'Sign in first before proceeding.');
        }

        return $next($request);
    }
}