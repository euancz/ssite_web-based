<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/auth/mobile/account', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate(['code' => ['required', 'string', 'size:64']]);
    [$user, $destination] = \App\Support\MobileAccountSession::consume($validated['code']);
    auth()->login($user);
    $request->session()->regenerate();
    return redirect()->route($destination)
        ->header('Cache-Control', 'no-store, private')
        ->header('Referrer-Policy', 'no-referrer');
})->middleware('throttle:20,1')->name('mobile.account.open');

// Single-use app handoff. Workspace routes retain their gates and policies.
Route::get('/auth/mobile/workspace', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate(['code' => ['required', 'string', 'size:64']]);
    $user = \App\Support\MobileWorkspace::consume($validated['code']);
    auth()->login($user);
    $request->session()->regenerate();
    return redirect()->route($user->dashboardRoute())
        ->header('Cache-Control', 'no-store, private')
        ->header('Referrer-Policy', 'no-referrer');
})->middleware('throttle:20,1')->name('mobile.workspace.open');

// The public landing page is separate from the authenticated CMS homepage.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : view('pages.landing');
})->name('landing');

Route::get('/home', HomeController::class)
    ->middleware([RequireAuthentication::class, EnsureProfileCompleted::class])
    ->name('home');

require __DIR__ . '/auth.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/features.php';
require __DIR__ . '/officer.php';
require __DIR__ . '/adviser.php';
