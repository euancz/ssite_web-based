<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = [
        'users' => 'user_id',
        'articles' => 'article_id',
        'activities' => 'activity_id',
        'achievements' => 'achievement_id',
        'documents' => 'document_id',
        'liquidations' => 'liquidation_id',
        'officer_terms' => 'term_id',
        'post_likes' => 'like_id',
        'post_views' => 'view_id',
        'content_drafts' => 'draft_id',
        'role_changes' => 'role_change_id',
    ];

    public function up(): void
    {
        $this->renameKeys(false);
    }

    public function down(): void
    {
        $this->renameKeys(true);
    }

    private function renameKeys(bool $reverse): void
    {
        $changes = [];
        foreach (self::KEYS as $table => $legacy) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            [$from, $to] = $reverse ? ['id', $legacy] : [$legacy, 'id'];
            if (! Schema::hasColumn($table, $from)) {
                continue;
            }

            // Validate every table before issuing DDL; MySQL DDL is not transactional.
            $primary = collect(Schema::getIndexes($table))->firstWhere('primary', true);
            if (Schema::hasColumn($table, $to) || ($primary['columns'] ?? []) !== [$from]) {
                throw new RuntimeException("Cannot rename {$table}.{$from}: expected a single primary key and no {$to} column.");
            }
            $changes[] = [$table, $from, $to];
        }

        foreach ($changes as [$table, $from, $to]) {
            // Renaming preserves IDs, types, auto-increment, indexes and FK references.
            Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
                $blueprint->renameColumn($from, $to);
            });
        }
    }
};
