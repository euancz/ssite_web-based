<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Achievement;
use App\Models\ContentDraft;
use App\Models\User;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ActivityAchievementEditorTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function enableFormatting(): void
    {
        (require database_path('migrations/2026_11_11_000001_add_content_format_to_activity_and_achievement_tables.php'))->up();
    }

    private function author(): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Editor', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => 'President', 'profile_completed_at' => now()])->save();

        return $user;
    }

    public function test_formatted_descriptions_survive_drafts_submission_and_editing(): void
    {
        $this->enableFormatting();
        $this->actingAs($this->author());
        $source = "## Highlights\n\n**Teamwork** and *learning*.\n\n- First\n- Second\n\n| Day | Event |\n| --- | --- |\n| Monday | Workshop |";

        foreach (['activities' => Activity::class, 'achievements' => Achievement::class] as $module => $model) {
            $singular = $module === 'activities' ? 'activity' : 'achievement';
            $this->get(route($module.'.create'))->assertOk()
                ->assertSee('data-rich-editor', false)->assertSee('data-rich-body', false)
                ->assertDontSee('data-rich-source', false)->assertSee('data-table-tools', false)
                ->assertSee('name="content_format" value="markdown"', false)
                ->assertSee('aria-labelledby="'.$singular.'-description-label"', false);
            $payload = ['title' => 'Highlights', 'description' => $source, 'content_format' => 'markdown'];
            $this->post(route('workspace.drafts.save', $module), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $draft = ContentDraft::firstOrFail();
            $this->assertSame('markdown', $draft->payload['content_format']);
            $this->get(route('workspace.drafts.edit', $draft))->assertOk()
                ->assertSee(e($source), false)->assertSee('data-rich-editor', false);
            $this->post(route($module.'.store'), $payload + ['draft_id' => $draft->getKey(), 'draft_version' => 1])
                ->assertSessionHasNoErrors()->assertRedirect();
            $post = $model::firstOrFail();
            $this->assertSame($source, $post->description);
            $this->assertSame('markdown', $post->content_format);
            $this->assertSame(0, ContentDraft::count());
            $this->get(route($module.'.show', $post))->assertOk()
                ->assertSee('<h2>Highlights</h2>', false)->assertSee('<strong>Teamwork</strong>', false)
                ->assertSee('<table>', false);
            $this->get(route($module.'.edit', $post))->assertOk()
                ->assertSee(e($source), false)->assertSee('data-rich-editor', false);
            $this->patch(route($module.'.update', $post), ['title' => 'Updated', 'description' => '**Updated** description'])
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame('markdown', $post->fresh()->content_format);
            $this->get(route('workspace.posts.'.$module.'.show', $post))->assertOk()
                ->assertSee('<strong>Updated</strong>', false);
        }
    }

    public function test_migration_preserves_plain_legacy_descriptions(): void
    {
        $author = $this->author();
        $this->actingAs($author);
        $posts = [];
        foreach (['activities' => Activity::class, 'achievements' => Achievement::class] as $module => $model) {
            $this->get(route($module.'.create'))->assertOk()->assertDontSee('data-rich-editor', false);
            $post = new $model;
            $post->forceFill(['title' => 'Legacy', 'description' => '**Literal** text',
                'created_by_user_id' => $author->id, 'approval_status' => 'approved', 'content_status' => 'active'])->save();
            $posts[$module] = $post;
        }
        $this->enableFormatting();
        foreach ($posts as $module => $post) {
            $this->assertSame('plain', $post->fresh()->content_format);
            $this->get(route($module.'.edit', $post))->assertOk()->assertSee('name="content_format" value="plain"', false);
            $this->get(route($module.'.show', $post))->assertOk()->assertSee('**Literal** text')
                ->assertDontSee('<strong>Literal</strong>', false);
        }
    }

    public function test_description_validation_keeps_editor_accessible_and_rejects_invalid_format(): void
    {
        $this->enableFormatting();
        $this->actingAs($this->author());
        foreach (['activities' => 'activity', 'achievements' => 'achievement'] as $module => $singular) {
            $this->from(route($module.'.create'))->post(route($module.'.store'), [
                'title' => 'Invalid', 'description' => '', 'content_format' => 'markdown',
            ])->assertSessionHasErrors('description');
            $this->get(route($module.'.create'))->assertOk()
                ->assertSee('aria-invalid="true" aria-describedby="'.$singular.'-description-error"', false);
            $this->post(route($module.'.store'), ['title' => 'Invalid format', 'description' => 'Body', 'content_format' => 'html'])
                ->assertSessionHasErrors('content_format');
            $this->post(route($module.'.store'), ['title' => 'Too long', 'description' => str_repeat('word ', 301), 'content_format' => 'markdown'])
                ->assertSessionHasErrors('description');
        }
        $this->assertSame(0, Activity::count());
        $this->assertSame(0, Achievement::count());
    }
}
