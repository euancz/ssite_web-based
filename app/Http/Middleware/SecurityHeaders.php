<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->isSecure()) {
            $origin = rtrim((string) config('app.url'), '/');
            abort_unless(str_starts_with($origin, 'https://'), 503, 'HTTPS must be configured before serving this application.');
            $parts = parse_url($origin);
            $origin = 'https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            $response = redirect()->away($origin.'/'.ltrim($request->getRequestUri(), '/'), 308);
        } else {
            $response = $next($request);
        }
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        return $response;
    }
}
