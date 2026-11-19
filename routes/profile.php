<?php

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

Route::middleware(RequireAuthentication::class . ':pending')->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'create'])
        ->name('profile.complete');
    Route::post('/profile/complete', [ProfileController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('profile.complete.store');
});

Route::middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    // SECURITY: Picture actions use the authenticated account and never accept a user ID.
    // SECURITY: Picture uploads are bounded even when a client repeatedly replaces the same file.
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture'])
        ->middleware('throttle:uploads')
        ->name('profile.picture.update');
    Route::delete('/profile/picture', [ProfileController::class, 'removePicture'])
        ->middleware('throttle:uploads')
        ->name('profile.picture.remove');
});
