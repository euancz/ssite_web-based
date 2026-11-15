<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Cross-database JSON expressions for Laravel's text notification payloads. */
final class NotificationSql
{
    public static function field(string $key): string
    {
        if (! in_array($key, ['type', 'post_type', 'post_id'], true)) {
            throw new \InvalidArgumentException('Unsupported notification field.');
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') return "(CAST(data AS jsonb)->>'{$key}')";

        $field = DB::connection()->getQueryGrammar()->wrap('data->'.$key);
        // SQLite extracts JSON numbers as integers; PostgreSQL ->> and MySQL JSON_UNQUOTE return text.
        return $key === 'post_id' && $driver === 'sqlite' ? 'CAST('.$field.' AS TEXT)' : $field;
    }

    public static function counts(): string
    {
        return self::field('type').' AS notification_type, '.self::field('post_type').' AS post_type, COUNT(*) AS total';
    }
}
