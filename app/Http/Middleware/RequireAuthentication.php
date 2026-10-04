<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect('/')->with('error', 'Sign in first before proceeding.');
        }

        return $next($request);
    }
}