<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final class ContentPagination
{
    /** Count only scoped/filtered records and keep stale links within the available pages. */
    public static function paginate(Builder|QueryBuilder|Relation $query, int $perPage = 5, bool $pending = false): LengthAwarePaginator
    {
        if ($pending) {
            return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
        }

        $query = $query instanceof Relation ? $query->getQuery() : $query;
        $countQuery = clone $query;
        $total = ($countQuery instanceof Builder ? $countQuery->toBase() : $countQuery)->getCountForPagination();
        $page = self::page($total, $perPage);

        return $query->paginate($perPage, ['*'], 'page', $page, $total)->onEachSide(1);
    }

    public static function page(int $total, int $perPage): int
    {
        return max(1, min(LengthAwarePaginator::resolveCurrentPage(), max(1, (int) ceil($total / $perPage))));
    }
}
