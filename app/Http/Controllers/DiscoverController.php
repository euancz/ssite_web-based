<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Services\FeaturedArticles;
use App\Support\ContentPagination;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
        $models = ['articles' => Article::class, 'activities' => Activity::class, 'achievements' => Achievement::class];
        $union = null;
        foreach ($models as $table => $model) {
            if (! Schema::hasTable($table) || ($filters['type'] !== 'all' && $filters['type'] !== $table)) continue;
            $query = $model::publiclyVisible()->select(['id', 'title', 'created_at'])->selectRaw('? AS module', [$table]);
            $query->selectRaw($table === 'articles' && app(FeaturedArticles::class)->available() ? 'featured_at' : 'NULL AS featured_at');
            if ($filters['q'] !== '') {
                $query->where(function ($query) use ($filters, $table) {
                    SearchText::contains($query, 'title', $filters['q']);
                    SearchText::contains($query, $table === 'articles' ? 'content' : 'description', $filters['q'], 'or');
                });
            }
            if ($since) $query->where('created_at', '>=', $since);
            $base = $query->toBase();
            $union = $union ? $union->unionAll($base) : $base;
        }

        if ($union) {
            $query = DB::query()->fromSub($union, 'discover_posts');
            if ($filters['sort'] === 'featured') $query->orderByRaw('CASE WHEN featured_at IS NULL THEN 1 ELSE 0 END')->orderByDesc('featured_at');
            match ($filters['sort']) {
                'oldest' => $query->orderBy('created_at'),
                'title' => $query->orderByRaw('LOWER(title) ASC')->orderByDesc('created_at'),
                default => $query->orderByDesc('created_at'),
            };
            $stories = ContentPagination::paginate($query->orderBy('module')->orderByDesc('id'), 9)->appends($filters);
            // Hydrate only this page, retaining the cross-module SQL order and bounded memory use.
            $loaded = [];
            foreach ($models as $module => $model) {
                $ids = $stories->getCollection()->where('module', $module)->pluck('id');
                if ($ids->isNotEmpty()) $loaded[$module] = $model::publiclyVisible()->with('author')->whereIn('id', $ids)->get()->keyBy('id');
            }
            $stories->setCollection($stories->getCollection()->map(fn ($row) => $loaded[$row->module]->get($row->id))->filter()->values());
        } else {
            $stories = new LengthAwarePaginator([], 0, 9, 1, ['path' => $request->url(), 'query' => $filters]);
        }
        $articles = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Article)->values());
        $activities = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Activity)->values());
        $achievements = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Achievement)->values());

        return view('pages.discover', compact('articles', 'activities', 'achievements', 'stories', 'filters'));
    }
}
