<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchSuggestions
{
    public static function requested(Request $request): bool
    {
        return $request->query('_suggest') === '1';
    }

    public static function fromQuery(Request $request, Builder|QueryBuilder $query, string $column = 'title'): JsonResponse
    {
        $term = $request->query('q', $request->query('search'));
        if (! is_string($term) || mb_strlen(trim($term)) < 2) {
            return self::response();
        }

        $results = $query->limit(20)->pluck($column)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->unique(fn (string $value) => mb_strtolower($value))
            ->take(6)->values()->all();

        return self::response($results);
    }

    public static function response(array $results = []): JsonResponse
    {
        return response()->json(['results' => $results])->header('Cache-Control', 'private, no-store');
    }
}
