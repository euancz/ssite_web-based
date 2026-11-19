<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class SearchText
{
    public static function operator(Builder|QueryBuilder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    public static function literal(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    public static function column(Builder|QueryBuilder $query, string $column): string
    {
        $base = $query instanceof Builder ? $query->getQuery() : $query;
        $wrapped = $base->getGrammar()->wrap($column);

        return in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "CONVERT($wrapped USING utf8mb4) COLLATE utf8mb4_unicode_ci"
            : $wrapped;
    }

    /** Bind literal search text, including SQL wildcards, with case-insensitive matching. */
    public static function contains(Builder|QueryBuilder $query, string $column, string $value, string $boolean = 'and'): void
    {
        $column = self::column($query, $column);
        $query->whereRaw($column.' '.self::operator($query)." ? ESCAPE '!'", ['%'.self::literal($value).'%'], $boolean);
    }
}
