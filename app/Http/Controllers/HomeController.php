<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\OfficerTerm;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Shows the home page using only public content. */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // SECURITY: Every home feature uses its public scope so private posts cannot appear here.
        // Some local environments do not have the content tables installed yet; keep the page renderable.
        $articles = Schema::hasTable('articles')
            ? Article::publiclyVisible()->with('author')->latest('created_at')->take(1)->get()
            : collect();
        $activities = Schema::hasTable('activities')
            ? Activity::publiclyVisible()->with('author')->latest('activity_date')->take(1)->get()
            : collect();
        $achievements = Schema::hasTable('achievements')
            ? Achievement::publiclyVisible()->with('author')->latest('achievement_date')->take(1)->get()
            : collect();
        $officers = Schema::hasTable('officer_terms')
            ? OfficerTerm::current()->inDisplayOrder()->get()
            : collect();

        return view('pages.home', compact('articles', 'activities', 'achievements', 'officers'));
    }
}
