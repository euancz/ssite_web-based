<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AboutController;
use Illuminate\Support\Facades\Route;

// SECURITY: Notification reads and state changes are confined to the authenticated, profile-complete owner.
Route::middleware(['auth', 'profile.complete'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/summary', [NotificationController::class, 'summary'])->name('summary');
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::get('/{notification}/avatar', [NotificationController::class, 'avatar'])->whereUuid('notification')->name('avatar');
    Route::post('/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->whereUuid('notification')->name('destroy');
});

// Holds shared content pages and post routes; feature listings accept guests, while writes use role gates.
// Public listing flags decide whether guests may browse metadata; all PDF routes remain authenticated.
$documentListMiddleware = config('school.documents_list_public', true)
    ? ['profile.complete'] : ['auth', 'profile.complete'];
$liquidationListMiddleware = config('school.liquidation_list_public', false)
    ? ['profile.complete'] : ['auth', 'profile.complete'];

Route::prefix('documents')->name('documents.')->middleware($documentListMiddleware)->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('show');
});
Route::prefix('documents')->name('documents.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/{document}/view', [DocumentController::class, 'viewFile'])->whereNumber('document')->name('view');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('download');
});
Route::prefix('documents')->name('documents.')->middleware(['auth', 'role:officer,adviser', 'profile.complete'])->group(function () {
    Route::get('/create', [DocumentController::class, 'create'])->name('create');
    Route::post('/', [DocumentController::class, 'store'])->name('store');
    Route::get('/{document}/edit', [DocumentController::class, 'edit'])->whereNumber('document')->name('edit');
    Route::patch('/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('update');
    Route::post('/{document}/archive', [DocumentController::class, 'archive'])->whereNumber('document')->name('archive');
    Route::post('/{document}/restore', [DocumentController::class, 'restore'])->whereNumber('document')->name('restore');
});
Route::prefix('documents')->name('documents.')->middleware(['auth', 'role:adviser', 'profile.complete'])->group(function () {
    Route::post('/bulk-archive', [DocumentController::class, 'bulkArchive'])->name('bulk-archive');
    Route::post('/{document}/approve', [DocumentController::class, 'approve'])->whereNumber('document')->name('approve');
    Route::post('/{document}/reject', [DocumentController::class, 'reject'])->whereNumber('document')->name('reject');
    Route::delete('/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('destroy');
});

Route::prefix('liquidation')->name('liquidations.')->middleware($liquidationListMiddleware)->group(function () {
    Route::get('/', [LiquidationController::class, 'index'])->name('index');
    Route::get('/{liquidation}', [LiquidationController::class, 'show'])->whereNumber('liquidation')->name('show');
});
Route::prefix('liquidation')->name('liquidations.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/{liquidation}/view', [LiquidationController::class, 'viewFile'])->whereNumber('liquidation')->name('view');
    Route::get('/{liquidation}/download', [LiquidationController::class, 'download'])->whereNumber('liquidation')->name('download');
});
Route::prefix('liquidation')->name('liquidations.')->middleware(['auth', 'role:officer,adviser', 'profile.complete'])->group(function () {
    Route::get('/create', [LiquidationController::class, 'create'])->name('create');
    Route::post('/', [LiquidationController::class, 'store'])->name('store');
    Route::get('/{liquidation}/edit', [LiquidationController::class, 'edit'])->whereNumber('liquidation')->name('edit');
    Route::patch('/{liquidation}', [LiquidationController::class, 'update'])->whereNumber('liquidation')->name('update');
    Route::post('/{liquidation}/archive', [LiquidationController::class, 'archive'])->whereNumber('liquidation')->name('archive');
    Route::post('/{liquidation}/restore', [LiquidationController::class, 'restore'])->whereNumber('liquidation')->name('restore');
});
Route::prefix('liquidation')->name('liquidations.')->middleware(['auth', 'role:adviser', 'profile.complete'])->group(function () {
    Route::post('/bulk-archive', [LiquidationController::class, 'bulkArchive'])->name('bulk-archive');
    Route::post('/{liquidation}/approve', [LiquidationController::class, 'approve'])->whereNumber('liquidation')->name('approve');
    Route::post('/{liquidation}/reject', [LiquidationController::class, 'reject'])->whereNumber('liquidation')->name('reject');
    Route::delete('/{liquidation}', [LiquidationController::class, 'destroy'])->whereNumber('liquidation')->name('destroy');
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
