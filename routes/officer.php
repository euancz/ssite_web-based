<?php

use App\Http\Controllers\Officer\DashboardController;
use Illuminate\Support\Facades\Route;

// Holds the officer dashboard; adviser and superadmin accounts use their role-assignment dashboard.
Route::get('/officer/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:officer', 'profile.complete'])
    ->name('officer.dashboard');
