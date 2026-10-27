<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class FeaturedArticles
{
    public function available(): bool
    {
        return Schema::hasColumn('articles', 'featured_at');
    }

    /** Keep publication boundaries ahead of editorial priority and provide recent fallbacks. */
    public function forDisplay(int $limit): Collection
    {
        $query = Article::publiclyVisible()->with('author');
        if ($this->available()) {
            $query->orderByDesc('featured_at');
        }

        return $query->latest('created_at')->orderByDesc('article_id')->limit($limit)->get();
    }
}
