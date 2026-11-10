<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrimaryKeyMigrationTest extends TestCase
{
    public function test_rename_preserves_rows_foreign_keys_uuid_keys_and_auto_increment(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->string('name');
        });
        Schema::create('articles', function (Blueprint $table): void {
            $table->increments('article_id');
            $table->unsignedInteger('created_by_user_id');
            $table->foreign('created_by_user_id')->references('user_id')->on('users')->restrictOnDelete();
            $table->string('title');
        });
        Schema::create('content_drafts', function (Blueprint $table): void {
            $table->uuid('draft_id')->primary();
        });
        DB::table('users')->insert(['user_id' => 42, 'name' => 'Preserved owner']);
        DB::table('articles')->insert(['article_id' => 91, 'created_by_user_id' => 42, 'title' => 'Preserved title']);
        $uuid = '0c44e6d9-7b92-4480-bcc1-295af1518aaf';
        DB::table('content_drafts')->insert(['draft_id' => $uuid]);

        $migration = require database_path('migrations/2026_11_10_000001_standardize_primary_keys.php');
        $migration->up();
        $migration->up(); // Safe to resume after a partially applied MySQL DDL operation.

        $this->assertDatabaseHas('users', ['id' => 42, 'name' => 'Preserved owner']);
        $this->assertDatabaseHas('articles', ['id' => 91, 'created_by_user_id' => 42, 'title' => 'Preserved title']);
        $this->assertDatabaseHas('content_drafts', ['id' => $uuid]);
        $foreign = Schema::getForeignKeys('articles')[0];
        $this->assertSame(['id'], $foreign['foreign_columns']);
        $this->assertGreaterThan(42, DB::table('users')->insertGetId(['name' => 'New owner']));
        try {
            DB::table('users')->where('id', 42)->delete();
            $this->fail('Existing foreign key protection must remain active.');
        } catch (QueryException) {
            $this->assertDatabaseHas('users', ['id' => 42]);
        }

        $migration->down();
        $this->assertDatabaseHas('articles', ['article_id' => 91, 'created_by_user_id' => 42]);
        $this->assertSame(['user_id'], Schema::getForeignKeys('articles')[0]['foreign_columns']);
        $this->assertDatabaseHas('content_drafts', ['draft_id' => $uuid]);
    }

    public function test_conflicting_columns_are_rejected_before_any_table_changes(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->increments('user_id'));
        Schema::create('articles', function (Blueprint $table): void {
            $table->increments('article_id');
            $table->integer('id');
        });

        try {
            (require database_path('migrations/2026_11_10_000001_standardize_primary_keys.php'))->up();
            $this->fail('Conflicting columns must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('articles.article_id', $exception->getMessage());
            $this->assertTrue(Schema::hasColumn('users', 'user_id'));
            $this->assertFalse(Schema::hasColumn('users', 'id'));
        }
    }
}
