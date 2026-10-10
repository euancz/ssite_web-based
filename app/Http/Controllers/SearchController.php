<?php

namespace App\Http\Controllers;

use App\Support\SiteSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Serves public search pages and the throttled header suggestion endpoint. */
class SearchController extends Controller
{
    private const LABELS = [
        'articles' => 'Articles',
        'activities' => 'Activities',
        'achievements' => 'Achievements',
        'documents' => 'Documents',
        'liquidation' => 'Liquidation',
        'officers' => 'Officers',
    ];

    /** Show visible results with per-type counts and a query-preserving page of ten. */
    public function index(Request $request, SiteSearch $search): View
    {
        $normalized = $search->normalize($this->queryValue($request));
        $types = $search->visibleTypes(! $request->user());
        // SECURITY: Short and empty queries return before any model query runs.
        $matches = $normalized['valid']
            ? $search->results($normalized['query'], $normalized['words'], $types)
            : collect();
        $counts = [];
        foreach ($types as $type) $counts[$type] = $matches->where('type', $type)->count();

        $activeType = $request->query('type', 'all');
        if (! in_array($activeType, array_merge(['all'], $types), true)) $activeType = 'all';
        $filtered = $activeType === 'all' ? $matches : $matches->where('type', $activeType)->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $results = new LengthAwarePaginator(
            $filtered->forPage($page, 10)->values(),
            $filtered->count(),
            10,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('search.index', [
            'query' => $normalized['query'],
            'words' => $normalized['words'],
            'validQuery' => $normalized['valid'],
            'types' => $types,
            'labels' => self::LABELS,
            'counts' => $counts,
            'activeType' => $activeType,
            'results' => $results,
            'search' => $search,
        ]);
    }

    /** Return plain-text suggestions only; the shared short-query rule avoids unnecessary endpoint queries. */
    public function suggest(Request $request, SiteSearch $search): JsonResponse
    {
        $normalized = $search->normalize($this->queryValue($request));
        if (! $normalized['valid']) return response()->json(['results' => []]);

        $types = $search->visibleTypes(! $request->user());
        $matches = $search->results($normalized['query'], $normalized['words'], $types);
        $suggestions = $matches->groupBy('type')->flatMap(fn ($items) => $items->take(3))
            ->take(10)->map(fn ($item) => [
                'type' => $item['type'],
                'title' => $item['title'],
                'excerpt' => $item['excerpt'],
                'url' => $item['url'],
                'meta' => $item['meta'],
            ])->values();

        return response()->json(['results' => $suggestions]);
    }

    /** Ignore malformed array query parameters rather than passing non-strings into normalization. */
    private function queryValue(Request $request): ?string
    {
        $query = $request->query('q');
        return is_string($query) ? $query : null;
    }
}
