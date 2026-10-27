<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class ArticleCategory
{
    public static function available(): bool
    {
        return Schema::hasColumn('articles', 'category');
    }
}
