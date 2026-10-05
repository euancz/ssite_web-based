<?php

use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->middleware('profile.complete');

Route::get('/home', function () {
    return view('pages.home');
})->middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->name('home');

require __DIR__ . '/auth.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/features.php';
require __DIR__ . '/officer.php';
require __DIR__ . '/adviser.php';
