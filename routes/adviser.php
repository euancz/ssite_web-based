<?php

use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\UserController;
use App\Http\Controllers\Adviser\OfficerTermController;
use Illuminate\Support\Facades\Route;

// Holds role-assignment routes for advisers and superadmins with completed profiles.
Route::prefix('adviser')
    ->name('adviser.')
    ->middleware(['auth', 'role:adviser,superadmin', 'profile.complete'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');
        Route::resource('users', UserController::class)->only(['index', 'show']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])
            ->middleware('can:changeRole,user')->name('users.update-role');
    });

// SECURITY: The historical review URL is now restricted to the President through a visible ability.
Route::get('/adviser/reviews', [App\Http\Controllers\Adviser\ReviewController::class, 'index'])
    ->middleware(['auth', 'role:officer', 'profile.complete', 'can:review-posts'])
    ->name('adviser.reviews.index');

// SECURITY: Officer roster routes are limited to the President through the manage-officers ability.
Route::prefix('adviser')->name('adviser.')->middleware(['auth', 'profile.complete', 'can:manage-officers'])->group(function () {
    Route::resource('officers', OfficerTermController::class)
        ->parameters(['officers' => 'officer'])->only(['index', 'create', 'store', 'edit', 'destroy']);
    Route::patch('/officers/{officer}', [OfficerTermController::class, 'update'])->name('officers.update');
    Route::post('/officers/copy-previous', [OfficerTermController::class, 'copyPrevious'])->name('officers.copy-previous');
});
