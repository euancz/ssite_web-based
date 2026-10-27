<?php

namespace App\Support;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;

/** Fixed module metadata; request input never supplies a table or column name. */
final class ContentModules
{
    public static function all(): array
    {
        return [
            'articles' => ['label' => 'Articles', 'singular' => 'Article', 'model' => Article::class],
            'activities' => ['label' => 'Activities', 'singular' => 'Activity', 'model' => Activity::class],
            'achievements' => ['label' => 'Achievements', 'singular' => 'Achievement', 'model' => Achievement::class],
            'documents' => ['label' => 'Documents', 'singular' => 'Document', 'model' => Document::class],
            'liquidations' => ['label' => 'Liquidations', 'singular' => 'Liquidation', 'model' => Liquidation::class],
        ];
    }
}
