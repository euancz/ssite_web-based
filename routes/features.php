<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Holds public feature pages and protected actions grouped by middleware.

// Public visitors and completed users can access search and public content pages.
Route::middleware(['profile.complete', 'throttle:60,1'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
});
Route::get('/about', AboutController::class)->name('about');
Route::get('/discover', DiscoverController::class)->name('discover');
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
Route::get('/transparency', [LiquidationController::class, 'index'])
    ->name('transparency')
    ->middleware($liquidationListMiddleware);
Route::resource('documents', DocumentController::class)->only(['index', 'show'])
    ->middleware($documentListMiddleware)->whereNumber('document');
Route::resource('liquidation', LiquidationController::class)->names('liquidations')
    ->parameters(['liquidation' => 'liquidation'])->only(['index', 'show'])
    ->middleware($liquidationListMiddleware)->whereNumber('liquidation');

// SECURITY: The President's editorial lists use separate workspace views; public URLs keep the student-facing views.
Route::prefix('editorial')->name('editorial.')->middleware(['auth', 'role:officer', 'profile.complete', 'can:review-posts'])->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/liquidations', [LiquidationController::class, 'index'])->name('liquidations.index');
});

// SECURITY: Workspace library URLs require a signed-in actor; each controller checks the module capability or review role.
Route::prefix('workspace/posts')->name('workspace.posts.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/articles/{article}', [ArticleController::class, 'show'])->whereNumber('article')->name('articles.show');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])->whereNumber('activity')->name('activities.show');
    Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])->whereNumber('achievement')->name('achievements.show');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('documents.show');
    Route::get('/liquidations/{liquidation}', [LiquidationController::class, 'show'])->whereNumber('liquidation')->name('liquidations.show');
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/liquidations', [LiquidationController::class, 'index'])->name('liquidations.index');
});

// SECURITY: One endpoint maps fixed post types and the controller rechecks feature, visibility, and policy.
Route::post('/posts/{type}/{post}/like', [PostLikeController::class, 'toggle'])
    ->where('type', 'article|activity|achievement|document|liquidation')
    ->whereNumber('post')
    ->middleware('throttle:likes')
    ->name('posts.like');

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
});
Route::prefix('liquidation')->name('liquidations.')->middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/{liquidation}/view', [LiquidationController::class, 'viewFile'])->whereNumber('liquidation')->name('view');
});

// SECURITY: Route middleware exposes each module capability in route:list; policies repeat authorization in controllers.
Route::middleware(['auth', 'profile.complete', 'throttle:content-writes'])->group(function () {
    // SECURITY: Upload routes have a tighter limit in addition to the shared write limit.
    Route::resource('articles', ArticleController::class)->only(['create', 'store'])->whereNumber('article')
        ->middleware('can:create,App\\Models\\Article')->middlewareFor('store', 'throttle:uploads');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->whereNumber('article')->name('articles.edit')->middleware('can:update,article');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])->whereNumber('article')->name('articles.update')->middleware(['can:update,article', 'throttle:uploads']);
    Route::post('/articles/{article}/archive', [ArticleController::class, 'archive'])->whereNumber('article')->name('articles.archive')->middleware('can:archive,article');
    Route::post('/articles/{article}/restore', [ArticleController::class, 'restore'])->whereNumber('article')->name('articles.restore')->middleware('can:restore,article');
    Route::post('/articles/bulk-archive', [ArticleController::class, 'bulkArchive'])->name('articles.bulk-archive')->middleware('can:bulkArchive,App\\Models\\Article');
    Route::post('/articles/{article}/approve', [ArticleController::class, 'approve'])->whereNumber('article')->name('articles.approve')->middleware('can:approve,article');
    Route::post('/articles/{article}/reject', [ArticleController::class, 'reject'])->whereNumber('article')->name('articles.reject')->middleware('can:reject,article');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->whereNumber('article')->name('articles.destroy')->middleware('can:delete,article');

    Route::resource('activities', ActivityController::class)->only(['create', 'store'])->whereNumber('activity')
        ->middleware('can:create,App\\Models\\Activity')->middlewareFor('store', 'throttle:uploads');
    Route::get('/activities/{activity}/edit', [ActivityController::class, 'edit'])->whereNumber('activity')->name('activities.edit')->middleware('can:update,activity');
    Route::patch('/activities/{activity}', [ActivityController::class, 'update'])->whereNumber('activity')->name('activities.update')->middleware(['can:update,activity', 'throttle:uploads']);
    Route::post('/activities/{activity}/archive', [ActivityController::class, 'archive'])->whereNumber('activity')->name('activities.archive')->middleware('can:archive,activity');
    Route::post('/activities/{activity}/restore', [ActivityController::class, 'restore'])->whereNumber('activity')->name('activities.restore')->middleware('can:restore,activity');
    Route::post('/activities/bulk-archive', [ActivityController::class, 'bulkArchive'])->name('activities.bulk-archive')->middleware('can:bulkArchive,App\\Models\\Activity');
    Route::post('/activities/{activity}/approve', [ActivityController::class, 'approve'])->whereNumber('activity')->name('activities.approve')->middleware('can:approve,activity');
    Route::post('/activities/{activity}/reject', [ActivityController::class, 'reject'])->whereNumber('activity')->name('activities.reject')->middleware('can:reject,activity');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->whereNumber('activity')->name('activities.destroy')->middleware('can:delete,activity');

    Route::resource('achievements', AchievementController::class)->only(['create', 'store'])->whereNumber('achievement')
        ->middleware('can:create,App\\Models\\Achievement')->middlewareFor('store', 'throttle:uploads');
    Route::get('/achievements/{achievement}/edit', [AchievementController::class, 'edit'])->whereNumber('achievement')->name('achievements.edit')->middleware('can:update,achievement');
    Route::patch('/achievements/{achievement}', [AchievementController::class, 'update'])->whereNumber('achievement')->name('achievements.update')->middleware(['can:update,achievement', 'throttle:uploads']);
    Route::post('/achievements/{achievement}/archive', [AchievementController::class, 'archive'])->whereNumber('achievement')->name('achievements.archive')->middleware('can:archive,achievement');
    Route::post('/achievements/{achievement}/restore', [AchievementController::class, 'restore'])->whereNumber('achievement')->name('achievements.restore')->middleware('can:restore,achievement');
    Route::post('/achievements/bulk-archive', [AchievementController::class, 'bulkArchive'])->name('achievements.bulk-archive')->middleware('can:bulkArchive,App\\Models\\Achievement');
    Route::post('/achievements/{achievement}/approve', [AchievementController::class, 'approve'])->whereNumber('achievement')->name('achievements.approve')->middleware('can:approve,achievement');
    Route::post('/achievements/{achievement}/reject', [AchievementController::class, 'reject'])->whereNumber('achievement')->name('achievements.reject')->middleware('can:reject,achievement');
    Route::delete('/achievements/{achievement}', [AchievementController::class, 'destroy'])->whereNumber('achievement')->name('achievements.destroy')->middleware('can:delete,achievement');

    Route::resource('documents', DocumentController::class)->only(['create', 'store'])->whereNumber('document')
        ->middleware('can:create,App\\Models\\Document')->middlewareFor('store', 'throttle:uploads');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->whereNumber('document')->name('documents.edit')->middleware('can:update,document');
    Route::patch('/documents/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update')->middleware(['can:update,document', 'throttle:uploads']);
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->whereNumber('document')->name('documents.archive')->middleware('can:archive,document');
    Route::post('/documents/{document}/restore', [DocumentController::class, 'restore'])->whereNumber('document')->name('documents.restore')->middleware('can:restore,document');
    Route::post('/documents/bulk-archive', [DocumentController::class, 'bulkArchive'])->name('documents.bulk-archive')->middleware('can:bulkArchive,App\\Models\\Document');
    Route::post('/documents/{document}/approve', [DocumentController::class, 'approve'])->whereNumber('document')->name('documents.approve')->middleware('can:approve,document');
    Route::post('/documents/{document}/reject', [DocumentController::class, 'reject'])->whereNumber('document')->name('documents.reject')->middleware('can:reject,document');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('documents.destroy')->middleware('can:delete,document');

    Route::resource('liquidation', LiquidationController::class)->names('liquidations')->parameters(['liquidation' => 'liquidation'])
        ->only(['create', 'store'])->whereNumber('liquidation')
        ->middleware('can:create,App\\Models\\Liquidation')->middlewareFor('store', 'throttle:uploads');
    Route::get('/liquidation/{liquidation}/edit', [LiquidationController::class, 'edit'])->whereNumber('liquidation')->name('liquidations.edit')->middleware('can:update,liquidation');
    Route::patch('/liquidation/{liquidation}', [LiquidationController::class, 'update'])->whereNumber('liquidation')->name('liquidations.update')->middleware(['can:update,liquidation', 'throttle:uploads']);
    Route::post('/liquidation/{liquidation}/archive', [LiquidationController::class, 'archive'])->whereNumber('liquidation')->name('liquidations.archive')->middleware('can:archive,liquidation');
    Route::post('/liquidation/{liquidation}/restore', [LiquidationController::class, 'restore'])->whereNumber('liquidation')->name('liquidations.restore')->middleware('can:restore,liquidation');
    Route::post('/liquidation/bulk-archive', [LiquidationController::class, 'bulkArchive'])->name('liquidations.bulk-archive')->middleware('can:bulkArchive,App\\Models\\Liquidation');
    Route::post('/liquidation/{liquidation}/approve', [LiquidationController::class, 'approve'])->whereNumber('liquidation')->name('liquidations.approve')->middleware('can:approve,liquidation');
    Route::post('/liquidation/{liquidation}/reject', [LiquidationController::class, 'reject'])->whereNumber('liquidation')->name('liquidations.reject')->middleware('can:reject,liquidation');
    Route::delete('/liquidation/{liquidation}', [LiquidationController::class, 'destroy'])->whereNumber('liquidation')->name('liquidations.destroy')->middleware('can:delete,liquidation');
});
