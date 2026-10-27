<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Services\FeaturedArticles;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Shows the latest publicly visible articles, activities, and achievements. */
class DiscoverController extends Controller
{
    public function __invoke(): View
    {
        // SECURITY: Discover uses the same public scope as the individual content pages.
        $articles = Schema::hasTable('articles')
            ? app(FeaturedArticles::class)->forDisplay(3)
            : collect();
        $activities = Schema::hasTable('activities')
            ? Activity::publiclyVisible()->with('author')->latest('created_at')->take(3)->get()
            : collect();
        $achievements = Schema::hasTable('achievements')
            ? Achievement::publiclyVisible()->with('author')->latest('created_at')->take(3)->get()
            : collect();

        return view('pages.discover', compact('articles', 'activities', 'achievements'));
    }
}
