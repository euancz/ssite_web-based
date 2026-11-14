<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Services\FeaturedArticles;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Shows the latest publicly visible articles, activities, and achievements. */
class DiscoverController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:all,articles,activities,achievements'],
            'period' => ['nullable', 'in:all,week,month,year'],
            'sort' => ['nullable', 'in:featured,recent,oldest,title'],
        ]);
        $filters = array_replace(['q' => '', 'type' => 'all', 'period' => 'all', 'sort' => 'featured'], array_filter($filters, fn ($value) => $value !== null));
        $filters['q'] = trim($filters['q']);
        $since = match ($filters['period']) {
            'week' => now()->subDays(7), 'month' => now()->subDays(30),
            'year' => now()->subYear(), default => null,
        };
        $collections = [];
        foreach (['articles' => Article::class, 'activities' => Activity::class, 'achievements' => Achievement::class] as $table => $model) {
            $collections[$table] = collect();
            if (! Schema::hasTable($table) || ($filters['type'] !== 'all' && $filters['type'] !== $table)) continue;
            // Apply every filter before limiting results, inside the public visibility boundary.
            $query = $model::publiclyVisible()->with('author');
            if ($filters['q'] !== '') {
                $query->where(function ($query) use ($filters, $table) {
                    $query->where('title', 'like', '%'.$filters['q'].'%')
                        ->orWhere($table === 'articles' ? 'content' : 'description', 'like', '%'.$filters['q'].'%');
                });
            }
            if ($since) $query->where('created_at', '>=', $since);
            if ($filters['sort'] === 'featured' && $table === 'articles' && app(FeaturedArticles::class)->available()) {
                $query->orderByDesc('featured_at');
            }
            match ($filters['sort']) {
                'oldest' => $query->orderBy('created_at'),
                'title' => $query->orderBy('title'),
                default => $query->orderByDesc('created_at'),
            };
            $collections[$table] = $query->orderByDesc('id')->limit(3)->get();
        }
        ['articles' => $articles, 'activities' => $activities, 'achievements' => $achievements] = $collections;

        return view('pages.discover', compact('articles', 'activities', 'achievements', 'filters'));
    }
}
