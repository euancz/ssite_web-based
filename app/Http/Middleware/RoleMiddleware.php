<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more named application roles.
 */
class RoleMiddleware
{
    /**
     * Reject users whose stored role is not listed on the route.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(
            $request->user() && in_array($request->user()->role, $roles, true),
            Response::HTTP_FORBIDDEN
        );

        return $next($request);
    }
}
