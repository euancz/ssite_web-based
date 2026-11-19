<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class ContentCategories
{
    public const GROUPS = [
        'documents' => ['Progdes & SaS', 'Memo & Resolution', 'Letters'],
        'achievements' => ['Organizational Awards', 'Competition Awards', 'Student Awards'],
        'articles' => ['Organization News', 'Student Features', 'Educational Articles', 'Event Highlights'],
        'activities' => ['Seminars and Webinars', 'Workshops', 'Competition', 'Community Outreach'],
    ];

    public static function available(string $module): bool
    {
        return Schema::hasColumn($module, 'category');
    }

    public static function options(string $module, ?Builder $query = null): array
    {
        $values = self::GROUPS[$module] ?? [];
        if ($query && self::available($module)) {
            $values = array_merge($values, (clone $query)->whereNotNull('category')->where('category', '<>', '')
                ->distinct()->orderBy('category')->pluck('category')->all());
        }

        return array_combine(array_unique($values), array_unique($values));
    }
}
