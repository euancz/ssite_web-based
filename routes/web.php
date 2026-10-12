<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Holds the public home page and its authenticated, profile-complete alias.
Route::get('/', HomeController::class)->middleware('profile.complete');

Route::get('/home', HomeController::class)
    ->middleware([RequireAuthentication::class, EnsureProfileCompleted::class])
    ->name('home');

require __DIR__ . '/auth.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/features.php';
require __DIR__ . '/officer.php';
require __DIR__ . '/adviser.php';
