<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupabaseSetupTest extends TestCase
{
    public function test_all_migrations_build_a_fresh_database_with_required_content_columns(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        foreach (['articles', 'activities', 'achievements', 'documents', 'liquidations'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, ['id', 'created_by_user_id', 'approval_status', 'content_status', 'view_count', 'like_count']));
        }
        $this->assertTrue(Schema::hasColumns('users', ['officer_position', 'profile_completed_at']));
        $this->assertTrue(Schema::hasColumns('role_changes', ['old_role', 'new_role', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('documents', ['semester', 'academic_year', 'file_path']));
    }

    public function test_media_copy_preserves_paths_and_original_files_and_can_resume(): void
    {
        // Isolated source roots: never read or change the user's real attachment collection.
        $originalStorage = $this->app->storagePath();
        $this->app->useStoragePath(storage_path('framework/testing/supabase-copy'));
        try {
            $publicSource = Storage::build(['driver' => 'local', 'root' => storage_path('app/public')]);
            $privateSource = Storage::build(['driver' => 'local', 'root' => storage_path('app/private')]);
            $publicSource->put('articles/test.jpg', 'image bytes');
            $privateSource->put('documents/test.pdf', '%PDF-test');
            Storage::fake('public');
            Storage::fake('local');
            config(['filesystems.disks.public.driver' => 's3', 'filesystems.disks.local.driver' => 's3']);
            $this->artisan('supabase:copy-media', ['--dry-run' => true])->assertSuccessful();
            Storage::disk('local')->assertMissing('documents/test.pdf');
            $this->artisan('supabase:copy-media')->assertSuccessful();
            $this->artisan('supabase:copy-media')->assertSuccessful();
            $this->assertSame('image bytes', Storage::disk('public')->get('articles/test.jpg'));
            $this->assertSame('%PDF-test', Storage::disk('local')->get('documents/test.pdf'));
            $this->assertTrue($publicSource->exists('articles/test.jpg'));
            $this->assertTrue($privateSource->exists('documents/test.pdf'));
            Storage::disk('local')->put('documents/test.pdf', 'different size');
            $this->artisan('supabase:copy-media')->assertFailed();
        } finally {
            $this->app->useStoragePath($originalStorage);
        }
    }
    public function test_export_preserves_ids_and_payloads_and_refuses_to_overwrite_backup(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::table('users')->insert(['id' => 42, 'name' => 'Sample student', 'email' => 'sample@example.test']);
        DB::table('articles')->insert(['id' => 99, 'title' => 'Existing post', 'created_by_user_id' => 42, 'image' => 'articles/old.jpg']);
        $path = storage_path('framework/testing/export-'.bin2hex(random_bytes(8)).'.jsonl');
        try {
            $this->artisan('supabase:transfer-data', ['action' => 'export', '--file' => $path])->assertSuccessful();
            $lines = array_map(fn ($line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), file($path, FILE_IGNORE_NEW_LINES));
            $this->assertSame(['format' => 'ssite-data-v1'], $lines[0]);
            $article = collect($lines)->firstWhere('table', 'articles')['row'];
            $this->assertSame(99, $article['id']);
            $this->assertSame(42, $article['created_by_user_id']);
            $this->assertSame('articles/old.jpg', $article['image']);
            $this->assertSame(['complete' => true], end($lines));
            $this->artisan('supabase:transfer-data', ['action' => 'export', '--file' => $path])->assertFailed();
            $this->artisan('supabase:transfer-data', ['action' => 'import', '--file' => $path])->assertFailed();
            $this->assertDatabaseHas('articles', ['id' => 99]);
        } finally {
            if (file_exists($path)) unlink($path);
        }
    }

    public function test_notification_json_is_cast_for_postgresql_text_columns(): void
    {
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('pgsql');
        try {
            $this->assertSame("(CAST(data AS jsonb)->>'type')", \App\Support\NotificationSql::field('type'));
        } finally {
            DB::setDefaultConnection($original);
        }
    }

    public function test_notification_counts_do_not_inherit_created_at_ordering(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::table('users')->insert(['id' => 1, 'name' => 'Test student', 'email' => 'counts@example.test', 'role' => 'student']);
        $user = \App\Models\User::findOrFail(1);
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test',
            'data' => ['type' => 'published', 'post_type' => 'article'],
        ]);
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create('/notifications/summary');
        $request->setUserResolver(fn () => $user);
        DB::enableQueryLog();
        try {
            $response = app(\App\Http\Controllers\NotificationController::class)->summary($request);
            $this->assertSame(1, $response->getData(true)['unread_count']);
            $view = view('layouts.app');
            app('view')->callComposer($view);
            $this->assertSame(1, $view->getData()['notificationUnreadCount']);
            $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($sql) => str_contains($sql, 'group by'));
            $this->assertCount(2, $queries);
            foreach ($queries as $sql) $this->assertStringNotContainsString('order by', strtolower($sql));
        } finally {
            DB::disableQueryLog();
        }
    }

}
