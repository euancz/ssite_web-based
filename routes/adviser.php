<?php

use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\ReviewController;
use App\Http\Controllers\Adviser\UserController;
use App\Http\Controllers\Adviser\OfficerTermController;
use Illuminate\Support\Facades\Route;

// Holds adviser dashboard, user-management, and review routes for advisers with completed profiles.
Route::prefix('adviser')
    ->name('adviser.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');
        Route::resource('users', UserController::class)->only(['index', 'show']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])
            ->name('users.update-role');
        Route::get('/reviews', [ReviewController::class, 'index'])
            ->name('reviews.index');
        Route::resource('officers', OfficerTermController::class)
            ->parameters(['officers' => 'officer'])
            ->only(['index', 'create', 'store', 'edit', 'destroy']);
        Route::patch('/officers/{officer}', [OfficerTermController::class, 'update'])->name('officers.update');
        Route::post('/officers/copy-previous', [OfficerTermController::class, 'copyPrevious'])->name('officers.copy-previous');
    });
