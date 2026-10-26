<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PostRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentWorkspaceTest extends TestCase
{
    use BuildsContentSchema;

    private const MODULES = [
        'articles' => ['article', 'Multimedia'], 'activities' => ['activity', 'Multimedia'],
        'achievements' => ['achievement', 'Multimedia'], 'documents' => ['document', 'Secretary'],
        'liquidations' => ['liquidation', 'Auditor'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function actor(string $role = 'officer', ?string $position = 'President'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Test member', 'email' => uniqid().'@mcc.edu.ph', 'role' => $role, 'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function makePost(string $module, int $owner, string $approval = 'approved', string $visibility = 'active'): int
    {
        [$type] = self::MODULES[$module];
        $data = ['title' => "$module $approval $visibility record", 'created_by_user_id' => $owner,
            'approval_status' => $approval, 'content_status' => $visibility, 'created_at' => now(), 'updated_at' => now()];
        $data[$type === 'article' ? 'content' : 'description'] = 'Content fixture';
        if (in_array($type, ['document', 'liquidation'], true)) {
            $data += ['file_path' => ($type === 'document' ? 'documents' : 'liquidation').'/'.str_repeat('a', 40).'.pdf', 'original_name' => 'record.pdf', 'file_size' => 15];
        }

        return DB::table($module)->insertGetId($data, $type.'_id');
    }

    public function test_public_routes_never_expose_private_content_or_management_controls_even_to_president(): void
    {
        $president = $this->actor();
        $student = $this->actor('student', null);
        foreach (self::MODULES as $module => [$type]) {
            $published = $this->makePost($module, $president->user_id);
            $private = [$this->makePost($module, $president->user_id, 'pending'), $this->makePost($module, $president->user_id, 'rejected'), $this->makePost($module, $president->user_id, 'approved', 'archived')];
            foreach ([$student, $president] as $actor) {
                $this->actingAs($actor);
                foreach (['published', 'archived', 'pending', 'mine', 'all'] as $tab) {
                    $this->get(route($module.'.index', ['tab' => $tab]))->assertOk()
                        ->assertSee("$module approved active record")
                        ->assertDontSee("$module pending active record")->assertDontSee("$module rejected active record")
                        ->assertDontSee("$module approved archived record")
                        ->assertDontSee('Edit post')->assertDontSee('Delete permanently')->assertDontSee('data-workspace-shell', false);
                }
                $this->get(route($module.'.show', $published))->assertOk()->assertDontSee('Edit post')->assertDontSee('data-workspace-shell', false);
                foreach ($private as $id) {
                    $this->get(route($module.'.show', $id))->assertNotFound();
                }
            }
        }
    }

    public function test_guests_cannot_recover_archived_content_with_query_parameters(): void
    {
        $owner = $this->actor();
        foreach (['articles', 'activities', 'achievements', 'documents'] as $module) {
            $id = $this->makePost($module, $owner->user_id, 'approved', 'archived');
            $this->get(route($module.'.index', ['tab' => 'archived']))->assertOk()->assertDontSee("$module approved archived record");
            $this->get(route($module.'.show', $id))->assertNotFound();
        }
    }

    public function test_workspace_submissions_are_module_and_owner_scoped(): void
    {
        foreach (self::MODULES as $module => [$type, $position]) {
            $owner = $this->actor('officer', $position);
            $other = $this->actor('officer', $position);
            $id = $this->makePost($module, $owner->user_id, 'pending');
            $foreign = $this->makePost($module, $other->user_id, 'rejected');
            $this->actingAs($owner)->get(route('workspace.posts.'.$module.'.index'))->assertOk()
                ->assertSee('My submissions')->assertSee("$module pending active record")->assertDontSee("$module rejected active record");
            $this->get(route('workspace.posts.'.$module.'.show', $id))->assertOk()->assertSee('Edit post');
            $this->get(route('workspace.posts.'.$module.'.show', $foreign))->assertNotFound();
            $this->post(route($module.'.approve', $id))->assertForbidden();
            $this->patch(route($module.'.update', $foreign), [])->assertForbidden();
            foreach (['student', 'adviser', 'superadmin'] as $role) {
                $this->actingAs($this->actor($role, null))->get(route('workspace.posts.'.$module.'.index'))->assertForbidden();
                $this->post(route($module.'.approve', $id))->assertForbidden();
            }
        }
    }

    public function test_review_requires_a_reason_and_restore_does_not_publish_rejected_content(): void
    {
        $president = $this->actor();
        foreach (self::MODULES as $module => [$type, $position]) {
            $owner = $this->actor('officer', $position);
            $id = $this->makePost($module, $owner->user_id, 'pending');
            $this->actingAs($president)->post(route($module.'.reject', $id), [])->assertSessionHasErrors('rejection_reason');
            $this->post(route($module.'.reject', $id), ['rejection_reason' => 'Please add the event details.'])->assertRedirect();
            $this->post(route($module.'.archive', $id))->assertRedirect();
            $this->post(route($module.'.restore', $id))->assertRedirect();
            $this->assertDatabaseHas($module, [$type.'_id' => $id, 'approval_status' => 'rejected', 'content_status' => 'active']);
            $this->get(route($module.'.show', $id))->assertNotFound();
        }
    }

    public function test_private_pdf_requires_owner_or_president_and_a_valid_signature(): void
    {
        Storage::fake('local');
        $owner = $this->actor('officer', 'Secretary');
        $id = $this->makePost('documents', $owner->user_id, 'pending');
        Storage::disk('local')->put('documents/'.str_repeat('a', 40).'.pdf', '%PDF-0123456789');
        $url = URL::temporarySignedRoute('documents.view', now()->addMinutes(5), ['document' => $id]);
        $this->actingAs($owner)->get(route('documents.view', $id))->assertNotFound();
        $this->get($url, ['Range' => 'bytes=0-4'])->assertStatus(206)->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->actor('student', null))->get($url)->assertNotFound();
        $this->actingAs($this->actor())->get($url)->assertOk();
        $this->travel(6)->minutes();
        $this->get($url)->assertNotFound();
    }

    public function test_json_contracts_and_notification_ownership_are_preserved(): void
    {
        $owner = $this->actor('officer', 'Multimedia');
        $president = $this->actor();
        $id = $this->makePost('articles', $owner->user_id, 'pending');
        $notificationId = (string) Str::uuid();
        $owner->notifications()->create([
            'id' => $notificationId, 'type' => PostRejected::class,
            'data' => ['type' => 'rejected', 'post_type' => 'article', 'post_id' => $id, 'title' => 'Submission', 'message' => 'Review feedback', 'url' => route('articles.show', $id), 'internal_field' => 'must not appear'],
        ]);
        $this->actingAs($owner)->getJson('/notifications/summary')->assertOk()
            ->assertJsonStructure(['unread_count', 'nav_counts', 'notifications' => [['id', 'read_at', 'created_at', 'data']]])
            ->assertJsonPath('unread_count', 1)->assertJsonMissing(['internal_field' => 'must not appear']);
        $this->getJson('/search/suggest?q=a')->assertOk()->assertExactJson(['results' => []]);
        $this->actingAs($president)->post(route('notifications.read', $notificationId))->assertNotFound();
        $this->actingAs($owner)->post(route('notifications.read', $notificationId))
            ->assertRedirect(route('workspace.posts.articles.show', $id));
    }
}
