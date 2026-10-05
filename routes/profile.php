<?php

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Profile completion is authenticated but exempt from profile.complete; edits require a completed profile.
Route::middleware(RequireAuthentication::class)->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'create'])
        ->name('profile.complete');
    Route::post('/profile/complete', [ProfileController::class, 'store'])
        ->name('profile.complete.store');
});

Route::middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});
