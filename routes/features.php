<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AboutController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Holds shared content pages and post routes; feature listings accept guests, while writes use role gates.
Route::middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->group(function () {
    Route::get('/liquidation', function () {
        return view('liquidation.index');
    })->name('liquidation');

    Route::get('/documents', function () {
        return view('documents.index');
    })->name('documents');
});

// About content is public; officer cards are read-only snapshots supplied by the controller.
Route::get('/about', AboutController::class)->name('about');

// Public Activity listing and detail pages filter results to approved and active records.
Route::middleware('profile.complete')->group(function () {
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])
        ->whereNumber('activity')
        ->name('activities.show');
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])
        ->whereNumber('achievement')
        ->name('achievements.show');
});

// Activity creation, editing, archive, and restore require completed officer or adviser accounts.
Route::prefix('activities')->name('activities.')
    ->middleware(['auth', 'role:officer,adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/create', [ActivityController::class, 'create'])->name('create');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->whereNumber('activity')->name('edit');
        Route::patch('/{activity}', [ActivityController::class, 'update'])->whereNumber('activity')->name('update');
        Route::post('/{activity}/archive', [ActivityController::class, 'archive'])->whereNumber('activity')->name('archive');
        Route::post('/{activity}/restore', [ActivityController::class, 'restore'])->whereNumber('activity')->name('restore');
    });

// Activity review, deletion, and bulk archive require completed adviser accounts.
Route::prefix('activities')->name('activities.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::post('/bulk-archive', [ActivityController::class, 'bulkArchive'])->name('bulk-archive');
        Route::post('/{activity}/approve', [ActivityController::class, 'approve'])->whereNumber('activity')->name('approve');
        Route::post('/{activity}/reject', [ActivityController::class, 'reject'])->whereNumber('activity')->name('reject');
        Route::delete('/{activity}', [ActivityController::class, 'destroy'])->whereNumber('activity')->name('destroy');
    });

// Achievement creation, editing, archive, and restore require completed officer or adviser accounts.
Route::prefix('achievements')->name('achievements.')
    ->middleware(['auth', 'role:officer,adviser', 'profile.complete'])
    ->group(function () {
        Route::get('/create', [AchievementController::class, 'create'])->name('create');
        Route::post('/', [AchievementController::class, 'store'])->name('store');
        Route::get('/{achievement}/edit', [AchievementController::class, 'edit'])->whereNumber('achievement')->name('edit');
        Route::patch('/{achievement}', [AchievementController::class, 'update'])->whereNumber('achievement')->name('update');
        Route::post('/{achievement}/archive', [AchievementController::class, 'archive'])->whereNumber('achievement')->name('archive');
        Route::post('/{achievement}/restore', [AchievementController::class, 'restore'])->whereNumber('achievement')->name('restore');
    });

// Achievement review, deletion, and bulk archive require completed adviser accounts.
Route::prefix('achievements')->name('achievements.')
    ->middleware(['auth', 'role:adviser', 'profile.complete'])
    ->group(function () {
        Route::post('/bulk-archive', [AchievementController::class, 'bulkArchive'])->name('bulk-archive');
        Route::post('/{achievement}/approve', [AchievementController::class, 'approve'])->whereNumber('achievement')->name('approve');
        Route::post('/{achievement}/reject', [AchievementController::class, 'reject'])->whereNumber('achievement')->name('reject');
        Route::delete('/{achievement}', [AchievementController::class, 'destroy'])->whereNumber('achievement')->name('destroy');
    });

// Public Articles listing and detail pages rely on ArticleController for approved/active visibility.
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
