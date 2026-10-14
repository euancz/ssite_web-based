<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Holds public feature pages and protected actions grouped by middleware.

// Public visitors and completed users can access search and public content pages.
Route::middleware(['profile.complete', 'throttle:60,1'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
});
Route::get('/about', AboutController::class)->name('about');
Route::resource('articles', ArticleController::class)->only(['index', 'show'])
    ->middleware('profile.complete')->whereNumber('article');
Route::resource('activities', ActivityController::class)->only(['index', 'show'])
    ->middleware('profile.complete')->whereNumber('activity');
Route::resource('achievements', AchievementController::class)->only(['index', 'show'])
    ->middleware('profile.complete')->whereNumber('achievement');

// Document and liquidation list access follows the configured public-list settings.
$documentListMiddleware = config('school.documents_list_public', true)
    ? ['profile.complete'] : ['auth', 'profile.complete'];
$liquidationListMiddleware = config('school.liquidation_list_public', false)
    ? ['profile.complete'] : ['auth', 'profile.complete'];
Route::resource('documents', DocumentController::class)->only(['index', 'show'])
    ->middleware($documentListMiddleware)->whereNumber('document');
Route::resource('liquidation', LiquidationController::class)->names('liquidations')
    ->parameters(['liquidation' => 'liquidation'])->only(['index', 'show'])
    ->middleware($liquidationListMiddleware)->whereNumber('liquidation');

// Signed-in users with completed profiles can use notifications and PDF delivery routes.
Route::prefix('notifications')->name('notifications.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/summary', [NotificationController::class, 'summary'])->name('summary');
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::get('/{notification}/avatar', [NotificationController::class, 'avatar'])->whereUuid('notification')->name('avatar');
    Route::post('/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->whereUuid('notification')->name('destroy');
});
Route::prefix('documents')->name('documents.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/{document}/view', [DocumentController::class, 'viewFile'])->whereNumber('document')->name('view');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('download');
});
Route::prefix('liquidation')->name('liquidations.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/{liquidation}/view', [LiquidationController::class, 'viewFile'])->whereNumber('liquidation')->name('view');
    Route::get('/{liquidation}/download', [LiquidationController::class, 'download'])->whereNumber('liquidation')->name('download');
});

// Officers, advisers, and superadmins can create, update, archive, or restore module content.
Route::middleware(['auth', 'role:officer,adviser,superadmin', 'profile.complete'])->group(function () {
    Route::resource('articles', ArticleController::class)->only(['create', 'store', 'edit'])->whereNumber('article');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])->whereNumber('article')->name('articles.update');
    Route::post('/articles/{article}/archive', [ArticleController::class, 'archive'])->whereNumber('article')->name('articles.archive');
    Route::post('/articles/{article}/restore', [ArticleController::class, 'restore'])->whereNumber('article')->name('articles.restore');

    Route::resource('activities', ActivityController::class)->only(['create', 'store', 'edit'])->whereNumber('activity');
    Route::patch('/activities/{activity}', [ActivityController::class, 'update'])->whereNumber('activity')->name('activities.update');
    Route::post('/activities/{activity}/archive', [ActivityController::class, 'archive'])->whereNumber('activity')->name('activities.archive');
    Route::post('/activities/{activity}/restore', [ActivityController::class, 'restore'])->whereNumber('activity')->name('activities.restore');

    Route::resource('achievements', AchievementController::class)->only(['create', 'store', 'edit'])->whereNumber('achievement');
    Route::patch('/achievements/{achievement}', [AchievementController::class, 'update'])->whereNumber('achievement')->name('achievements.update');
    Route::post('/achievements/{achievement}/archive', [AchievementController::class, 'archive'])->whereNumber('achievement')->name('achievements.archive');
    Route::post('/achievements/{achievement}/restore', [AchievementController::class, 'restore'])->whereNumber('achievement')->name('achievements.restore');

    Route::resource('documents', DocumentController::class)->only(['create', 'store', 'edit'])->whereNumber('document');
    Route::patch('/documents/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->whereNumber('document')->name('documents.archive');
    Route::post('/documents/{document}/restore', [DocumentController::class, 'restore'])->whereNumber('document')->name('documents.restore');

    Route::resource('liquidation', LiquidationController::class)->names('liquidations')
        ->parameters(['liquidation' => 'liquidation'])->only(['create', 'store', 'edit'])->whereNumber('liquidation');
    Route::patch('/liquidation/{liquidation}', [LiquidationController::class, 'update'])->whereNumber('liquidation')->name('liquidations.update');
    Route::post('/liquidation/{liquidation}/archive', [LiquidationController::class, 'archive'])->whereNumber('liquidation')->name('liquidations.archive');
    Route::post('/liquidation/{liquidation}/restore', [LiquidationController::class, 'restore'])->whereNumber('liquidation')->name('liquidations.restore');
});

// Advisers and superadmins review, and superadmins alone permanently delete, bulk-archive, or manage approval changes.
Route::middleware(['auth', 'role:adviser,superadmin', 'profile.complete'])->group(function () {
    Route::post('/articles/bulk-archive', [ArticleController::class, 'bulkArchive'])->name('articles.bulk-archive');
    Route::post('/articles/{article}/approve', [ArticleController::class, 'approve'])->whereNumber('article')->name('articles.approve');
    Route::post('/articles/{article}/reject', [ArticleController::class, 'reject'])->whereNumber('article')->name('articles.reject');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->whereNumber('article')->name('articles.destroy');

    Route::post('/activities/bulk-archive', [ActivityController::class, 'bulkArchive'])->name('activities.bulk-archive');
    Route::post('/activities/{activity}/approve', [ActivityController::class, 'approve'])->whereNumber('activity')->name('activities.approve');
    Route::post('/activities/{activity}/reject', [ActivityController::class, 'reject'])->whereNumber('activity')->name('activities.reject');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->whereNumber('activity')->name('activities.destroy');

    Route::post('/achievements/bulk-archive', [AchievementController::class, 'bulkArchive'])->name('achievements.bulk-archive');
    Route::post('/achievements/{achievement}/approve', [AchievementController::class, 'approve'])->whereNumber('achievement')->name('achievements.approve');
    Route::post('/achievements/{achievement}/reject', [AchievementController::class, 'reject'])->whereNumber('achievement')->name('achievements.reject');
    Route::delete('/achievements/{achievement}', [AchievementController::class, 'destroy'])->whereNumber('achievement')->name('achievements.destroy');

    Route::post('/documents/bulk-archive', [DocumentController::class, 'bulkArchive'])->name('documents.bulk-archive');
    Route::post('/documents/{document}/approve', [DocumentController::class, 'approve'])->whereNumber('document')->name('documents.approve');
    Route::post('/documents/{document}/reject', [DocumentController::class, 'reject'])->whereNumber('document')->name('documents.reject');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('documents.destroy');

    Route::post('/liquidation/bulk-archive', [LiquidationController::class, 'bulkArchive'])->name('liquidations.bulk-archive');
    Route::post('/liquidation/{liquidation}/approve', [LiquidationController::class, 'approve'])->whereNumber('liquidation')->name('liquidations.approve');
    Route::post('/liquidation/{liquidation}/reject', [LiquidationController::class, 'reject'])->whereNumber('liquidation')->name('liquidations.reject');
    Route::delete('/liquidation/{liquidation}', [LiquidationController::class, 'destroy'])->whereNumber('liquidation')->name('liquidations.destroy');
});
