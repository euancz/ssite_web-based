<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\PostImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class PrivatePostImagesTest extends TestCase
{
    use BuildsContentSchema;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function actor(string $role = 'officer', string $position = 'Social Media Manager'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Fixture', 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();
        return $user;
    }

    private function record(User $owner, string $path): Article
    {
        $post = new Article(['title' => 'Private submission', 'content' => 'Body', 'image' => $path]);
        $post->forceFill(['created_by_user_id' => $owner->id, 'approval_status' => 'pending', 'content_status' => 'active'])->save();
        Storage::disk(PostImages::disk($path))->put($path, base64_decode(self::PNG));
        return $post;
    }

    public function test_uploads_for_all_image_modules_stay_private_before_and_after_review(): void
    {
        $owner = $this->actor();
        $this->actingAs($owner);
        foreach (['articles', 'activities', 'achievements'] as $module) {
            $this->post(route($module.'.store'), ['title' => 'New post', 'content' => 'Body', 'description' => 'Body',
                'image' => UploadedFile::fake()->createWithContent('photo.png', base64_decode(self::PNG)),
            ])->assertRedirect()->assertSessionHasNoErrors();
            $path = DB::table($module)->value('image');
            $this->assertStringStartsWith('post-media/'.$module.'/', $path);
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_image_access_tracks_publication_ownership_and_current_capability(): void
    {
        $owner = $this->actor();
        $post = $this->record($owner, 'post-media/articles/fixture.png');
        $url = $post->imageUrl();
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->actor('student', ''))->get($url)->assertNotFound();
        $this->actingAs($owner)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->actor())->get($url)->assertNotFound();
        $this->actingAs($this->actor('officer', 'President'))->get($url)->assertOk();
        $post->forceFill(['approval_status' => 'approved'])->save();
        $this->actingAs($this->actor('student', ''))->get($url)->assertOk();
        $post->forceFill(['content_status' => 'archived'])->save();
        $this->get($url)->assertNotFound();
        $owner->forceFill(['officer_position' => 'Secretary'])->save();
        $this->actingAs($owner)->get($url)->assertNotFound();
    }

    public function test_legacy_image_migration_verifies_copies_updates_shared_references_and_revokes_public_files(): void
    {
        $owner = $this->actor();
        $first = $this->record($owner, 'articles/legacy.png');
        $second = $this->record($owner, 'articles/legacy.png');
        $this->artisan('media:privatize-post-images')->assertSuccessful();
        Storage::disk('public')->assertExists('articles/legacy.png');
        $this->assertSame('articles/legacy.png', $first->fresh()->image);
        $this->artisan('media:privatize-post-images', ['--apply' => true])->assertSuccessful();
        $this->assertSame('post-media/articles/legacy.png', $first->fresh()->image);
        $this->assertSame($first->fresh()->image, $second->fresh()->image);
        $this->assertSame(base64_decode(self::PNG), Storage::disk('local')->get($first->fresh()->image));
        Storage::disk('public')->assertMissing('articles/legacy.png');
        $this->artisan('media:privatize-post-images', ['--apply' => true])->assertSuccessful();
    }

    public function test_missing_legacy_file_does_not_change_database_references(): void
    {
        $post = $this->record($this->actor(), 'articles/missing.png');
        Storage::disk('public')->delete($post->image);
        $this->artisan('media:privatize-post-images', ['--apply' => true])->assertFailed();
        $this->assertSame('articles/missing.png', $post->fresh()->image);
    }
    public function test_draft_image_is_persisted_privately_and_an_explicit_replacement_takes_precedence(): void
    {
        $owner = $this->actor();
        $this->actingAs($owner);
        foreach ([false, true] as $replace) {
            $this->post(route('workspace.drafts.save', 'articles'), ['title' => 'Draft', 'content' => 'Body',
                'image' => UploadedFile::fake()->createWithContent('draft.png', base64_decode(self::PNG)),
            ])->assertRedirect()->assertSessionHasNoErrors();
            $draft = \App\Models\ContentDraft::firstOrFail();
            $attachment = $draft->attachment();
            $data = ['draft_id' => $draft->getKey(), 'draft_version' => $draft->lock_version, 'title' => 'Submitted', 'content' => 'Body'];
            if ($replace) $data['image'] = UploadedFile::fake()->createWithContent('replacement.png', base64_decode(self::PNG));
            $this->post(route('articles.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
            $post = Article::latest('id')->firstOrFail();
            $this->assertStringStartsWith('post-media/articles/', $post->image);
            Storage::disk('local')->assertExists($post->image);
            Storage::disk('local')->assertMissing($attachment['path']);
            $this->assertDatabaseCount('content_drafts', 0);
        }
        $this->assertCount(2, Storage::disk('local')->files('post-media/articles'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

}
