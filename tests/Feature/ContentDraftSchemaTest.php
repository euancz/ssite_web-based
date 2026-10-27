<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentDraftSchemaTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema(false);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_27_000001_create_content_drafts_table.php');
    }

    public function test_additive_migration_preserves_content_and_supports_private_draft_payloads(): void
    {
        $owner = DB::table('users')->insertGetId(['name' => 'Draft author'], 'user_id');
        DB::table('articles')->insert(['title' => 'Existing published article', 'content' => 'Keep this text',
            'created_by_user_id' => $owner, 'approval_status' => 'approved', 'content_status' => 'active']);
        $before = DB::table('articles')->get()->toJson();

        $this->migration()->up();
        $id = (string) Str::uuid();
        DB::table('content_drafts')->insert(['draft_id' => $id, 'created_by_user_id' => $owner,
            'module' => 'articles', 'payload' => json_encode(['title' => 'Unfinished', 'content' => 'Private text']),
            'created_at' => now(), 'updated_at' => now()]);

        $draft = DB::table('content_drafts')->where('draft_id', $id)->first();
        $this->assertSame(1, (int) $draft->lock_version);
        $this->assertSame('Private text', json_decode($draft->payload, true)['content']);
        $this->assertSame($before, DB::table('articles')->get()->toJson());
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(0, DB::table('notifications')->count());
        $this->assertTrue(collect(Schema::getIndexes('content_drafts'))->contains(
            fn (array $index) => $index['columns'] === ['created_by_user_id', 'module', 'updated_at']
        ));
    }

    public function test_draft_owner_must_reference_an_existing_legacy_user_key(): void
    {
        $this->migration()->up();
        $this->expectException(QueryException::class);
        DB::table('content_drafts')->insert(['draft_id' => (string) Str::uuid(),
            'created_by_user_id' => 999, 'module' => 'articles', 'payload' => '{}']);
    }

    public function test_deleting_an_owner_does_not_silently_delete_their_drafts(): void
    {
        $owner = DB::table('users')->insertGetId(['name' => 'Draft author'], 'user_id');
        $this->migration()->up();
        DB::table('content_drafts')->insert(['draft_id' => (string) Str::uuid(),
            'created_by_user_id' => $owner, 'module' => 'documents', 'payload' => '{}']);

        try {
            DB::table('users')->where('user_id', $owner)->delete();
            $this->fail('A user with saved drafts must not be deleted implicitly.');
        } catch (QueryException) {
            $this->assertSame(1, DB::table('content_drafts')->count());
            $this->assertSame(1, DB::table('users')->count());
        }
    }

    public function test_existing_draft_table_is_not_silently_adopted_or_replaced(): void
    {
        $this->migration()->up();
        $this->expectException(QueryException::class);
        $this->migration()->up();
    }

    public function test_isolated_rollback_removes_only_the_new_draft_table(): void
    {
        $owner = DB::table('users')->insertGetId(['name' => 'Preserved user'], 'user_id');
        DB::table('articles')->insert(['title' => 'Preserved article', 'created_by_user_id' => $owner]);
        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasTable('content_drafts'));
        $this->assertSame('Preserved user', DB::table('users')->value('name'));
        $this->assertSame('Preserved article', DB::table('articles')->value('title'));
    }
}
