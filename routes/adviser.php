<?php

use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\ReviewController;
use App\Http\Controllers\Adviser\UserController;
use Illuminate\Support\Facades\Route;

// Holds adviser dashboard, user-management, and review routes for advisers with completed profiles.
Route::prefix('adviser')
    ->name('adviser.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])
            ->name('users.update-role');
        Route::get('/reviews', [ReviewController::class, 'index'])
            ->name('reviews.index');
    });
