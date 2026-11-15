<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class SearchText
{
    public static function operator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    public static function literal(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /** Bind literal search text, including SQL wildcards, with case-insensitive matching. */
    public static function contains(Builder $query, string $column, string $value, string $boolean = 'and'): void
    {
        $column = $query->getQuery()->getGrammar()->wrap($column);
        $query->whereRaw($column.' '.self::operator($query)." ? ESCAPE '!'", ['%'.self::literal($value).'%'], $boolean);
    }
}
