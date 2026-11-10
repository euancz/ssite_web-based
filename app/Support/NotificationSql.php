<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Cross-database JSON expressions for Laravel's text notification payloads. */
final class NotificationSql
{
    public static function field(string $key): string
    {
        if (! in_array($key, ['type', 'post_type'], true)) {
            throw new \InvalidArgumentException('Unsupported notification field.');
        }

        return DB::connection()->getDriverName() === 'pgsql'
            ? "(CAST(data AS jsonb)->>'{$key}')"
            : DB::connection()->getQueryGrammar()->wrap('data->'.$key);
    }

    public static function counts(): string
    {
        return self::field('type').' AS notification_type, '.self::field('post_type').' AS post_type, COUNT(*) AS total';
    }
}
