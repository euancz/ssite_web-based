<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Computes academic-year labels from the configured start month so rollover needs no scheduled job.
 */
class AcademicYear
{
    public static function current(): string
    {
        $now = Carbon::now();
        $startMonth = (int) config('school.academic_year_start_month', 6);
        $startYear = $now->month >= $startMonth ? $now->year : $now->year - 1;

        return $startYear . '-' . ($startYear + 1);
    }

    /** Check a consecutive YYYY-YYYY label before it is used for writes. */
    public static function isValid(string $ay): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $ay, $matches) === 1
            && (int) $matches[2] === (int) $matches[1] + 1;
    }

    /** Build recent and upcoming choices relative to the current academic year. */
    public static function options(int $back = 5, int $forward = 1): array
    {
        [$start] = array_map('intval', explode('-', static::current()));
        $years = [];

        for ($year = $start - max(0, $back); $year <= $start + max(0, $forward); $year++) {
            $years[] = $year . '-' . ($year + 1);
        }

        return array_reverse($years);
    }
}
