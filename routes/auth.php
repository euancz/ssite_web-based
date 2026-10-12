<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Holds sign-in and Microsoft callback routes for guests, plus logout for authenticated users.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'redirectToHome'])->name('login');

    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
        ->name('microsoft.login');

    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
        ->name('microsoft.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(RequireAuthentication::class)
    ->name('logout');
