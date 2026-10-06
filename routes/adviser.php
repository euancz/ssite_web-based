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
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])
            ->name('users.update-role');
        Route::get('/reviews', [ReviewController::class, 'index'])
            ->name('reviews.index');
        Route::get('/officers', [OfficerTermController::class, 'index'])->name('officers.index');
        Route::get('/officers/create', [OfficerTermController::class, 'create'])->name('officers.create');
        Route::post('/officers', [OfficerTermController::class, 'store'])->name('officers.store');
        Route::post('/officers/copy-previous', [OfficerTermController::class, 'copyPrevious'])->name('officers.copy-previous');
        Route::get('/officers/{officer}/edit', [OfficerTermController::class, 'edit'])->name('officers.edit');
        Route::patch('/officers/{officer}', [OfficerTermController::class, 'update'])->name('officers.update');
        Route::delete('/officers/{officer}', [OfficerTermController::class, 'destroy'])->name('officers.destroy');
    });
