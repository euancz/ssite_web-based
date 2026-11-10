<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Microsoft is the only sign-in route; logout remains available to authenticated users.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'redirectToHome'])->name('login');

    Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
        ->name('microsoft.login');

    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
        ->name('microsoft.callback');
});

Route::get('/auth/microsoft/mobile', [MicrosoftAuthController::class, 'redirectForMobile'])
    ->name('microsoft.mobile.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(RequireAuthentication::class)
    ->name('logout');
