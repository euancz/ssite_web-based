<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Middleware\RequireAuthentication;


// ==============================
// PUBLIC / ROOT
// ==============================

Route::get('/', function () {
    return view('home.home');
});

// ==============================
// GUEST ROUTES
// ==============================

Route::middleware('guest')->group(function () {

    // Normal login
    Route::get('/login', function () {
        return redirect('/')->with('error', 'Sign in first before proceeding.');
    })
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);


    // Microsoft Login
    Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
        ->name('microsoft.login');

    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
        ->name('microsoft.callback');
});


// ==============================
// AUTHENTICATED ROUTES
// ==============================

Route::middleware(RequireAuthentication::class)->group(function () {
Route::get('/about', function () {
    return view('about.about');
})->name('about');

//home
 Route::get('/home', function () {
    return view('home.home');
})->name('home');

Route::get('/articles', function () {
    return view('articles.articles');
})->name('articles');

Route::get('/activities', function () {
    return view('activities.activities');
})->name('activities');

Route::get('/achievements', function () {
    return view('achievements.achievements');
})->name('achievements');
Route::get('/liquidation', function () {
    return view('liquidation.liquidation');
})->name('liquidation');

Route::get('/documents', function () {
    return view('documents.documents');
})->name('documents');



    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

