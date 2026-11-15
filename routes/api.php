<?php

use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\ContentController;
use App\Http\Controllers\Mobile\SessionController;
use App\Http\Middleware\AuthenticateMobileToken;
use Illuminate\Support\Facades\Route;

// Published mobile content and private files retain token authentication.
Route::prefix('mobile/v1')->name('api.mobile.')->group(function () {
    Route::post('/auth/exchange', [AuthController::class, 'exchange'])->middleware('throttle:10,1')->name('auth.exchange');

    Route::middleware(AuthenticateMobileToken::class)->group(function () {
        Route::post('/account/session', [SessionController::class, 'account'])->middleware('throttle:10,1')->name('account.session');

        Route::get('/discover', [ContentController::class, 'discover'])->name('discover');

        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::post('/workspace/session', [SessionController::class, 'workspace'])->middleware('throttle:10,1')->name('workspace.session');

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/home', [ContentController::class, 'home'])->name('home');

        Route::get('/about', [ContentController::class, 'about'])->name('about');

        Route::get('/{module}', [ContentController::class, 'index'])->where('module', 'articles|activities|achievements|documents|liquidations')->name('content');

        Route::get('/files/{module}/{id}', [ContentController::class, 'file'])->where(['module' => 'documents|liquidations', 'id' => '[0-9]+'])->name('file');

        Route::get('/media/{module}/{id}', [ContentController::class, 'media'])->where(['module' => 'articles|activities|achievements', 'id' => '[0-9]+'])
            ->name('media');
    });
});
