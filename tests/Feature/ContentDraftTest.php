<?php

namespace Tests\Feature;

use App\Models\ContentDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentDraftTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function actor(string $position = 'Multimedia'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Draft owner', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function saveDraft(User $owner, string $module = 'articles', array $values = []): ContentDraft
    {
        $this->actingAs($owner)->post(route('workspace.drafts.save', $module), $values)->assertRedirect();

        return ContentDraft::where('created_by_user_id', $owner->user_id)->latest('created_at')->firstOrFail();
    }

    public function test_all_modules_save_and_resume_incomplete_private_drafts_without_publishing_or_notifying(): void
    {
        foreach (['articles' => 'Multimedia', 'activities' => 'Multimedia', 'achievements' => 'Multimedia', 'documents' => 'Secretary', 'liquidations' => 'Auditor'] as $module => $position) {
            $owner = $this->actor($position);
            $draft = $this->saveDraft($owner, $module, ['title' => 'Private '.$module, 'created_by_user_id' => 999,
                'approval_status' => 'approved', 'file_path' => 'secret.pdf', 'image' => 'forged.jpg']);
            $this->assertSame(['title' => 'Private '.$module], $draft->payload);
            $this->assertSame($owner->user_id, $draft->created_by_user_id);
            $this->assertSame(0, DB::table($module)->count());
            $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertSee('Private '.$module)->assertSee('name="draft_version"', false);
            $this->get(route('workspace.drafts.index'))->assertOk()->assertSee('Private '.$module);
        }
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_other_officers_and_president_cannot_access_save_submit_or_delete_another_authors_draft(): void
    {
        $owner = $this->actor();
        $draft = $this->saveDraft($owner, 'articles', ['title' => 'Owners secret']);
        foreach ([$this->actor(), $this->actor('President')] as $other) {
            $this->actingAs($other)->get(route('workspace.drafts.index'))->assertOk()->assertDontSee('Owners secret');
            $this->get(route('workspace.drafts.edit', $draft))->assertNotFound();
            $values = ['draft_id' => $draft->getKey(), 'draft_version' => 1, 'title' => 'Forged', 'content' => 'Text'];
            $this->post(route('workspace.drafts.save', 'articles'), $values)->assertNotFound();
            $this->post(route('articles.store'), $values)->assertNotFound();
            $this->delete(route('workspace.drafts.destroy', $draft), ['draft_version' => 1])->assertNotFound();
        }
        $this->assertSame(0, DB::table('articles')->count());
        $this->assertSame(1, ContentDraft::count());
    }

    public function test_stale_saves_submissions_and_deletions_preserve_the_latest_draft_and_cleanup_uploads(): void
    {
        Storage::fake('public');
        $owner = $this->actor();
        $draft = $this->saveDraft($owner, 'articles', ['title' => 'Version one']);
        $values = ['draft_id' => $draft->getKey(), 'draft_version' => 1, 'title' => 'Version two', 'content' => 'Text'];
        $this->post(route('workspace.drafts.save', 'articles'), $values)->assertRedirect();
        $this->assertSame(2, $draft->fresh()->lock_version);
        $this->post(route('workspace.drafts.save', 'articles'), $values)->assertSessionHasErrors('draft_version');
        $this->post(route('articles.store'), $values + ['image' => UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='))])->assertSessionHasErrors('draft_version');
        $this->delete(route('workspace.drafts.destroy', $draft), ['draft_version' => 1])->assertSessionHasErrors('draft_version');
        $this->assertSame('Version two', $draft->fresh()->payload['title']);
        $this->assertSame(0, DB::table('articles')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_successful_submission_consumes_draft_once_and_uses_existing_review_workflow(): void
    {
        $this->actor('President');
        $owner = $this->actor();
        $draft = $this->saveDraft($owner, 'articles', ['title' => 'Unfinished']);
        $values = ['draft_id' => $draft->getKey(), 'draft_version' => 1, 'title' => 'Ready article', 'content' => 'Ready text', 'approval_status' => 'approved'];
        $this->post(route('articles.store'), $values)->assertRedirect(route('workspace.posts.articles.show', 1));
        $this->assertSame(0, ContentDraft::count());
        $this->assertSame('pending', DB::table('articles')->value('approval_status'));
        $this->assertSame($owner->user_id, DB::table('articles')->value('created_by_user_id'));
        $notifications = DB::table('notifications')->count();
        $this->assertGreaterThan(0, $notifications);
        $this->post(route('articles.store'), $values)->assertNotFound();
        $this->assertSame(1, DB::table('articles')->count());
        $this->assertSame($notifications, DB::table('notifications')->count());
    }

    public function test_pdf_draft_requires_file_on_submission_and_never_stores_files_during_draft_save(): void
    {
        Storage::fake('local');
        $owner = $this->actor('Secretary');
        $draftPdf = UploadedFile::fake()->createWithContent('record.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $draft = $this->saveDraft($owner, 'documents', ['title' => 'Minutes', 'file' => $draftPdf, 'semester' => '2']);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertArrayNotHasKey('file', $draft->payload);
        $this->assertSame('pdf', $draft->payload['attachment']['type']);
        $values = ['draft_id' => $draft->getKey(), 'draft_version' => 1, 'title' => 'Minutes', 'semester' => '2'];
        $this->post(route('documents.store'), $values)->assertSessionHasErrors('file');
        $this->assertSame(1, ContentDraft::count());
        $pdf = UploadedFile::fake()->createWithContent('minutes.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->post(route('documents.store'), $values + ['file' => $pdf])->assertRedirect(route('workspace.posts.documents.show', 1));
        $this->assertSame(0, ContentDraft::count());
        $this->assertSame(1, DB::table('documents')->count());
        $this->assertSame('minutes.pdf', DB::table('documents')->value('original_name'));
        $this->assertSame([], Storage::disk('local')->allFiles('drafts'));
    }

    public function test_role_changes_hide_drafts_and_cross_module_submission_is_rejected(): void
    {
        $owner = $this->actor();
        $draft = $this->saveDraft($owner, 'articles');
        $this->post(route('activities.store'), ['draft_id' => $draft->getKey(), 'draft_version' => 1, 'title' => 'Wrong area', 'description' => 'Text'])->assertNotFound();
        $this->assertSame(0, DB::table('activities')->count());
        $owner->forceFill(['officer_position' => 'Secretary'])->save();
        $this->actingAs($owner)->get(route('workspace.drafts.edit', $draft))->assertNotFound();
        $this->post(route('workspace.drafts.save', 'articles'), ['title' => 'Denied'])->assertForbidden();
        $this->get(route('workspace.drafts.index'))->assertOk()->assertViewHas('drafts', fn ($drafts) => $drafts->total() === 0);
        $this->assertSame(1, ContentDraft::count());
    }

    public function test_draft_validation_escapes_markup_and_enforces_field_bounds_but_allows_long_work_in_progress(): void
    {
        $owner = $this->actor();
        $draft = $this->saveDraft($owner, 'articles', ['title' => '<script>alert(1)</script>', 'content' => str_repeat('word ', 301)]);
        $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $this->post(route('workspace.drafts.save', 'articles'), ['title' => str_repeat('a', 201)])->assertSessionHasErrors('title');
        $this->post(route('workspace.drafts.save', 'articles'), ['content' => ['not text']])->assertSessionHasErrors('content');
        $this->post(route('articles.store'), ['draft_id' => $draft->getKey(), 'draft_version' => 1] + $draft->payload)->assertSessionHasErrors('content');
        $this->assertSame(1, ContentDraft::count());
        $this->delete(route('workspace.drafts.destroy', $draft), ['draft_version' => 1])->assertRedirect(route('workspace.drafts.index'));
        $this->assertSame(0, ContentDraft::count());
    }
}
