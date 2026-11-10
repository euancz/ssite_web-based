<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;

        // Laravel connects as the database owner. Browser/mobile clients use Laravel's API.
        // Deny direct Supabase Data API access to app tables, including users and sessions.
        foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
            'jobs', 'job_batches', 'failed_jobs', 'articles', 'activities', 'achievements',
            'documents', 'liquidations', 'officer_terms', 'role_changes', 'notifications',
            'post_likes', 'post_views', 'content_drafts', 'content_review_events', 'content_versions', 'migrations'] as $table) {
            if (Schema::hasTable($table)) {
                DB::statement('ALTER TABLE '.DB::connection()->getQueryGrammar()->wrapTable($table).' ENABLE ROW LEVEL SECURITY');
            }
        }
    }

    public function down(): void
    {
        // Retain protection during rollbacks.
    }
};
