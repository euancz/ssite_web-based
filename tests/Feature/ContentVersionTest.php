<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\ContentVersions;
use App\Services\PostFiles;
use App\Support\ContentModules;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentVersionTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        (require database_path('migrations/2026_10_27_000006_create_content_versions_table.php'))->up();
    }

    private function actor(string $position = 'President'): User
    {
        $user = new User;
        $user->forceFill(['name' => $position, 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer', 'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function articleRecord(User $owner): Article
    {
        $post = new Article(['title' => 'Original', 'content' => '<script>private()</script>']);
        $post->created_by_user_id = $owner->getKey();
        $post->save();

        return $post->fresh();
    }

    public function test_first_edit_preserves_baseline_and_versions_are_private_escaped_and_paginated(): void
    {
        $owner = $this->actor('Multimedia');
        $post = $this->articleRecord($owner);
        $this->actingAs($owner);
        for ($i = 1; $i <= 11; $i++) {
            $post->title = 'Edit '.$i;
            app(PostFiles::class)->save($post);
        }
        $this->assertDatabaseCount('content_versions', 12);
        $baseline = DB::table('content_versions')->first();
        $this->assertSame('Original', json_decode($baseline->snapshot, true)['title']);
        $this->assertNull($baseline->editor_user_id);
        $url = route('workspace.content-versions.index', ['module' => 'articles', 'post' => $post->getKey()]);
        $this->get($url)->assertOk()->assertSee('Edit 11')->assertDontSee('<script>private()', false);
        // Historical source is escaped even when it contains markup.
        $this->get($url.'?page=2')->assertOk()->assertSee('&lt;script&gt;private()&lt;/script&gt;', false)->assertDontSee('<script>private()', false);
        $this->actingAs($this->actor('Multimedia'))->get($url)->assertForbidden();
        $this->actingAs($this->actor())->get($url)->assertOk();
        $owner->officer_position = 'Secretary';
        $owner->save();
        $this->actingAs($owner)->get($url)->assertForbidden();
        $post->delete();
        $this->actingAs($this->actor())->get($url)->assertNotFound();
    }

    public function test_all_modules_capture_creation_and_changes_but_not_workflow_or_file_paths(): void
    {
        $owner = $this->actor();
        $this->actingAs($owner);
        foreach (ContentModules::all() as $module) {
            $post = new $module['model'];
            $post->forceFill(['title' => 'Original', 'created_by_user_id' => $owner->getKey()]);
            if (in_array($post->getTable(), ['documents', 'liquidations'], true)) {
                $post->forceFill(['file_path' => 'private/example.pdf', 'original_name' => 'example.pdf', 'file_size' => 100]);
            }
            if ($post instanceof Article) {
                $post->content = 'Text';
            } else {
                $post->description = 'Text';
            }
            app(PostFiles::class)->save($post);
            $post->refresh();
            $post->title = 'Changed';
            app(PostFiles::class)->save($post);
            $post->content_status = 'archived';
            app(PostFiles::class)->save($post);
        }
        $this->assertDatabaseCount('content_versions', 10);
        foreach (DB::table('content_versions')->get() as $version) {
            $snapshot = json_decode($version->snapshot, true);
            $this->assertArrayNotHasKey('file_path', $snapshot);
            $this->assertArrayNotHasKey('image', $snapshot);
            $this->assertArrayNotHasKey('approval_status', $snapshot);
            $this->assertSame($owner->getKey(), $version->editor_user_id);
        }
    }

    public function test_version_failure_rolls_back_content_and_callback_failure_rolls_back_versions(): void
    {
        $post = $this->articleRecord($this->actor());
        $post->title = 'Must roll back';
        try {
            app(PostFiles::class)->save($post, fn () => throw new \RuntimeException('Abort transaction'));
            $this->fail('Expected rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Abort transaction', $exception->getMessage());
        }
        $this->assertSame('Original', $post->fresh()->title);
        $this->assertDatabaseCount('content_versions', 0);
        DB::statement("CREATE TRIGGER reject_version BEFORE INSERT ON content_versions BEGIN SELECT RAISE(ABORT, 'Version unavailable'); END");
        $post = $post->fresh();
        $post->title = 'Still must roll back';
        try {
            app(PostFiles::class)->save($post);
            $this->fail('Expected failed version insert.');
        } catch (QueryException) {
            $this->assertSame('Original', $post->fresh()->title);
        }
    }

    public function test_missing_schema_preserves_existing_writes_and_hides_history(): void
    {
        Schema::drop('content_versions');
        $owner = $this->actor();
        $post = $this->articleRecord($owner);
        $post->title = 'Compatible edit';
        app(PostFiles::class)->save($post);
        $this->assertSame('Compatible edit', $post->fresh()->title);
        $this->actingAs($owner)->get(route('workspace.content-versions.index', ['module' => 'articles', 'post' => $post->getKey()]))->assertNotFound();
    }

    public function test_comparison_uses_previous_version_of_same_post_and_escapes_source(): void
    {
        $owner = $this->actor('Multimedia');
        $this->actingAs($owner);
        $post = $this->articleRecord($owner);
        $post->title = 'First edit';
        app(PostFiles::class)->save($post);
        $other = $this->articleRecord($owner);
        $other->title = 'Other post secret';
        app(PostFiles::class)->save($other);
        $post->content = '<img src=x onerror=alert(1)>';
        app(PostFiles::class)->save($post);
        $selected = DB::table('content_versions')->max('id');
        $url = route('workspace.content-versions.index', ['module' => 'articles', 'post' => $post->getKey()]);
        $this->get($url.'?compare='.$selected)->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('Other post secret')
            ->assertViewHas('comparison', fn ($comparison) => $comparison['previous']->id === 2
                && count($comparison['changes']) === 1 && $comparison['changes'][0]['field'] === 'content');
        $this->get($url.'?compare=1')->assertOk()->assertSee('There is no earlier version to compare.');
        $this->get($url.'?compare=3')->assertNotFound();
        $this->get($url.'?compare=999999')->assertNotFound();
        $this->getJson($url.'?compare[]=1')->assertUnprocessable();
        $this->getJson($url.'?compare=-1')->assertUnprocessable();
        $this->actingAs($this->actor('Multimedia'))->get($url.'?compare='.$selected)->assertForbidden();
    }

    public function test_comparison_preserves_absent_null_empty_and_zero_values(): void
    {
        $history = app(ContentVersions::class);
        $changes = $history->changes('{"title":"Same","amount":0,"description":null,"removed":"old"}', '{"title":"Same","amount":0,"description":"","new":null}');
        $this->assertCount(3, $changes);
        $this->assertSame('description', $changes[0]['field']);
        $this->assertNull($changes[0]['before']);
        $this->assertSame('', $changes[0]['after']);
        $this->assertFalse($changes[1]['has_field']);
        $this->assertFalse($changes[2]['had_field']);
        $this->assertSame([], $history->changes('{"amount":0}', '{"amount":0}'));
    }
}
