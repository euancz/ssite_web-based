<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use Illuminate\View\View;

/** Shows the home page using only public content. */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // SECURITY: Every home feature uses its public scope so private posts cannot appear here.
        $articles = Article::publiclyVisible()->with('author')->latest('created_at')->take(1)->get();
        $activities = Activity::publiclyVisible()->with('author')->latest('activity_date')->take(1)->get();
        $achievements = Achievement::publiclyVisible()->with('author')->latest('achievement_date')->take(1)->get();

        return view('pages.home', compact('articles', 'activities', 'achievements'));
    }
}
