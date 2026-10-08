<?php

use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\RequireAuthentication;
use App\Models\Article;
use App\Models\Achievement;
use App\Models\Activity;
use Illuminate\Support\Facades\Route;

$homePage = function () {
    // SECURITY: Every home feature uses its public scope so private posts cannot appear here.
    $articles = Article::publiclyVisible()->with('author')->latest('created_at')->take(1)->get();
    $activities = Activity::publiclyVisible()->with('author')->latest('activity_date')->take(1)->get();
    $achievements = Achievement::publiclyVisible()->with('author')->latest('achievement_date')->take(1)->get();

    return view('pages.home', compact('articles', 'activities', 'achievements'));
};

Route::get('/', $homePage)->middleware('profile.complete');

Route::get('/home', $homePage)
    ->middleware([RequireAuthentication::class, EnsureProfileCompleted::class])
    ->name('home');

require __DIR__ . '/auth.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/features.php';
require __DIR__ . '/officer.php';
require __DIR__ . '/adviser.php';
