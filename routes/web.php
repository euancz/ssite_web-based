<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

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
