<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\OfficerDashboardController;
use App\Http\Controllers\Adviser\DashboardController as AdviserDashboardController;
use App\Http\Controllers\Adviser\ReviewController;
use App\Http\Controllers\Adviser\UserController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;


// ==============================
// PUBLIC / ROOT
// ==============================

// Root is public with profile.complete middleware: guests pass through, incomplete signed-in users go to completion.

Route::get('/', function () {
    return view('home.home');
})->middleware('profile.complete');

// ==============================
// GUEST ROUTES
// ==============================

// The guest middleware lets any role start sign-in; the Microsoft callback stays outside profile.complete.
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

// These auth-only routes serve every role and intentionally omit profile.complete so the form remains reachable.
Route::middleware(RequireAuthentication::class)->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'create'])
        ->name('profile.complete');
    Route::post('/profile/complete', [ProfileController::class, 'store'])
        ->name('profile.complete.store');
});

// RequireAuthentication plus EnsureProfileCompleted protect shared pages and profile edits for every role.
Route::middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->group(function () {
Route::get('/about', function () {
    return view('about.about');
})->name('about');

//home
 Route::get('/home', function () {
    return view('home.home');
})->name('home');

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

Route::get('/profile/edit', [ProfileController::class, 'edit'])
    ->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])
    ->name('profile.update');

});

// Public Articles listing and detail pages allow guests and rely on the controller for approved/active visibility.
Route::middleware('profile.complete')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{article}', [ArticleController::class, 'show'])
        ->whereNumber('article')
        ->name('articles.show');
});

// Article posting, editing, archive, and restore actions require a completed officer or adviser account.
Route::prefix('articles')->name('articles.')
    ->middleware(['auth', 'role:officer,adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/create', [ArticleController::class, 'create'])->name('create');
        Route::post('/', [ArticleController::class, 'store'])->name('store');
        Route::get('/{article}/edit', [ArticleController::class, 'edit'])->whereNumber('article')->name('edit');
        Route::patch('/{article}', [ArticleController::class, 'update'])->whereNumber('article')->name('update');
        Route::post('/{article}/archive', [ArticleController::class, 'archive'])->whereNumber('article')->name('archive');
        Route::post('/{article}/restore', [ArticleController::class, 'restore'])->whereNumber('article')->name('restore');
    });

// Article review, permanent deletion, and bulk archive are adviser-only and profile-gated.
Route::prefix('articles')->name('articles.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::post('/bulk-archive', [ArticleController::class, 'bulkArchive'])->name('bulk-archive');
        Route::post('/{article}/approve', [ArticleController::class, 'approve'])->whereNumber('article')->name('approve');
        Route::post('/{article}/reject', [ArticleController::class, 'reject'])->whereNumber('article')->name('reject');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])->whereNumber('article')->name('destroy');
    });

// Officer and adviser dashboards require both the matching role and a completed profile.
Route::get('/officer/dashboard', OfficerDashboardController::class)
    ->middleware(['auth', 'role:officer,adviser', 'profile.complete'])
    ->name('officer.dashboard');

// Adviser-only user and review tools; profile enforcement also protects these write-capable routes.
Route::prefix('adviser')
    ->name('adviser.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/dashboard', AdviserDashboardController::class)
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

// Logout deliberately skips profile completion so an incomplete user can always leave the session.
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(RequireAuthentication::class)
    ->name('logout');
