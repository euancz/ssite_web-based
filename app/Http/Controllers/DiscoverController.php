<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Support\ContentPagination;
use App\Support\InitialPageData;
use App\Support\SearchText;
use App\Support\SearchSuggestions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Shows the latest publicly visible articles, activities, and achievements. */
class DiscoverController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:all,articles,activities,achievements'],
            'period' => ['nullable', 'in:all,week,month,year'],
            'sort' => ['nullable', 'in:featured,recent,oldest,title'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $filters = array_replace(['q' => '', 'type' => 'all', 'period' => 'all', 'sort' => 'recent', 'category' => ''], array_filter($filters, fn ($value) => $value !== null));
        if ($filters['sort'] === 'featured') {
            $filters['sort'] = 'recent';
        }
        $filters['q'] = trim($filters['q']);
        if (! SearchSuggestions::requested($request) && InitialPageData::pending($request)) {
            return view('pages.discover', ['pageDataPending' => true, 'filters' => $filters]);
        }

        $since = match ($filters['period']) {
            'week' => now()->subDays(7), 'month' => now()->subDays(30),
            'year' => now()->subYear(), default => null,
        };
        $models = ['articles' => Article::class, 'activities' => Activity::class, 'achievements' => Achievement::class];
        $categoryGroups = [];
        foreach ($models as $table => $model) {
            if ($filters['type'] === $table && \App\Support\ContentCategories::available($table)) {
                $categoryGroups[ucfirst($table)] = \App\Support\ContentCategories::options($table, $model::publiclyVisible());
            }
        }
        // Category filters belong to the selected content type; discard stale URL values.
        if (! collect($categoryGroups)->contains(fn ($options) => array_key_exists($filters['category'], $options))) {
            $filters['category'] = '';
        }
        $paginationFilters = array_filter($filters, fn ($value) => $value !== '');
        $union = null;
        foreach ($models as $table => $model) {
            if (! Schema::hasTable($table) || ($filters['type'] !== 'all' && $filters['type'] !== $table)) continue;
            $query = $model::publiclyVisible()->select(['id', 'title', 'created_at'])->selectRaw('? AS module', [$table]);
            if ($filters['category'] !== '') {
                if (! \App\Support\ContentCategories::available($table)) continue;
                $query->where('category', $filters['category']);
            }
            if ($filters['q'] !== '') {
                $query->where(function ($query) use ($filters, $table) {
                    SearchText::contains($query, 'title', $filters['q']);
                    if (\App\Support\ContentCategories::available($table)) SearchText::contains($query, 'category', $filters['q'], 'or');
                    SearchText::contains($query, $table === 'articles' ? 'content' : 'description', $filters['q'], 'or');
                });
            }
            if ($since) $query->where('created_at', '>=', $since);
            $base = $query->toBase();
            $union = $union ? $union->unionAll($base) : $base;
        }

        if ($union) {
            $query = DB::query()->fromSub($union, 'discover_posts');
            match ($filters['sort']) {
                'oldest' => $query->orderBy('created_at'),
                'title' => $query->orderByRaw('LOWER(title) ASC')->orderByDesc('created_at'),
                default => $query->orderByDesc('created_at'),
            };
            if (SearchSuggestions::requested($request)) {
                return SearchSuggestions::fromQuery($request, $query->orderBy('module')->orderByDesc('id'));
            }
            $stories = ContentPagination::paginate($query->orderBy('module')->orderByDesc('id'), 9)->appends($paginationFilters);
            // Hydrate only this page, retaining the cross-module SQL order and bounded memory use.
            $loaded = [];
            foreach ($models as $module => $model) {
                $ids = $stories->getCollection()->where('module', $module)->pluck('id');
                if ($ids->isNotEmpty()) $loaded[$module] = $model::publiclyVisible()->with('author')->whereIn('id', $ids)->get()->keyBy('id');
            }
            $stories->setCollection($stories->getCollection()->map(fn ($row) => $loaded[$row->module]->get($row->id))->filter()->values());
        } else {
            if (SearchSuggestions::requested($request)) {
                return SearchSuggestions::response();
            }
            $stories = new LengthAwarePaginator([], 0, 9, 1, ['path' => $request->url(), 'query' => $paginationFilters]);
        }
        $articles = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Article)->values());
        $activities = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Activity)->values());
        $achievements = new Collection($stories->getCollection()->filter(fn ($post) => $post instanceof Achievement)->values());

        return view('pages.discover', compact('articles', 'activities', 'achievements', 'stories', 'filters', 'categoryGroups'));
    }
}
