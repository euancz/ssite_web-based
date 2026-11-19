<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RoleMiddleware;
use App\Support\MicrosoftAccount;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn (Request $request) => $request->session()->has(MicrosoftAccount::PENDING_SESSION_KEY)
            ? route('profile.complete')
            : route('login'));
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Aliases keep role and profile checks readable in route definitions.
        $middleware->alias([
            'profile.complete' => EnsureProfileCompleted::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
