<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AboutController;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Holds shared content pages and Articles routes; ordinary feature pages require completed sign-in,
// while Article listing/show accept guests and Article writes require the existing role middleware.
Route::middleware([RequireAuthentication::class, EnsureProfileCompleted::class])->group(function () {
    Route::get('/activities', function () {
        return view('activities.index');
    })->name('activities');

    Route::get('/achievements', function () {
        return view('achievements.index');
    })->name('achievements');

    Route::get('/liquidation', function () {
        return view('liquidation.index');
    })->name('liquidation');

    Route::get('/documents', function () {
        return view('documents.index');
    })->name('documents');
});

// About content is public; officer cards are read-only snapshots supplied by the controller.
Route::get('/about', AboutController::class)->name('about');

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
