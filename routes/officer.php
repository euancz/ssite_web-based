<?php

use App\Http\Controllers\Officer\DashboardController;
use Illuminate\Support\Facades\Route;

// Holds the officer dashboard, available to officers and advisers with completed profiles.
Route::get('/officer/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:officer,adviser', 'profile.complete'])
    ->name('officer.dashboard');
