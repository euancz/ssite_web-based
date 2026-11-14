<?php

use App\Http\Controllers\Officer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('workspace')->name('workspace.')
    ->middleware(['auth', 'profile.complete', 'can:manage-sponsors'])
    ->group(function () {
        Route::resource('sponsors', \App\Http\Controllers\SponsorController::class)->except('show');
    });

// Holds the officer dashboard; adviser and superadmin accounts use their role-assignment dashboard.
Route::get('/officer/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:officer', 'profile.complete'])
    ->name('officer.dashboard');
