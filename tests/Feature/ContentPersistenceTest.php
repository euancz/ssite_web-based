<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use App\Services\ContentReview;
use App\Services\PostFiles;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentPersistenceTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function actor(string $position): User
    {
        $user = new User;
        $user->forceFill(['name' => $position, 'email' => $position.'@mcc.edu.ph', 'role' => 'officer', 'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function article(User $owner): Article
    {
        $article = new Article(['title' => 'Original post', 'content' => 'Original content', 'image' => 'articles/original.jpg']);
        $article->created_by_user_id = $owner->user_id;
        $article->save();
        Storage::disk('public')->put($article->image, 'original bytes');

        return $article->fresh();
    }

    public function test_failed_database_save_keeps_old_file_and_removes_new_upload(): void
    {
        $article = $this->article($this->actor('Multimedia'));
        Storage::disk('public')->put('articles/replacement.jpg', 'replacement bytes');
        $article->image = 'articles/replacement.jpg';
        $article->title = null;
        try {
            app(PostFiles::class)->save($article);
            $this->fail('An invalid database write must fail.');
        } catch (QueryException) {
            Storage::disk('public')->assertExists('articles/original.jpg');
            Storage::disk('public')->assertMissing('articles/replacement.jpg');
            $this->assertSame('articles/original.jpg', $article->fresh()->image);
        }
    }

    public function test_successful_save_replaces_file_and_stale_edits_are_rejected(): void
    {
        $article = $this->article($this->actor('Multimedia'));
        $stale = $article->fresh();
        Storage::disk('public')->put('articles/replacement.jpg', 'replacement bytes');
        $article->image = 'articles/replacement.jpg';
        $article->title = 'Updated post';
        app(PostFiles::class)->save($article);
        Storage::disk('public')->assertMissing('articles/original.jpg');
        Storage::disk('public')->assertExists('articles/replacement.jpg');
        $stale->title = 'Outdated overwrite';
        try {
            app(PostFiles::class)->save($stale);
            $this->fail('A stale edit must not overwrite newer content.');
        } catch (ValidationException) {
            $this->assertSame('Updated post', $article->fresh()->title);
        }
    }

    public function test_review_of_a_changed_record_is_rejected_and_repeated_approval_sends_no_duplicates(): void
    {
        $owner = $this->actor('Multimedia');
        $president = $this->actor('President');
        $article = $this->article($owner);
        Article::whereKey($article->getKey())->update(['content' => 'Changed during review']);
        $this->assertFalse(app(ContentReview::class)->decide($article, $president, true));
        $this->assertDatabaseCount('notifications', 0);
        $this->actingAs($president)->post(route('articles.approve', $article))->assertRedirect();
        $this->assertDatabaseHas('articles', ['article_id' => $article->getKey(), 'approval_status' => 'approved', 'reviewed_by_user_id' => $president->user_id]);
        $count = DB::table('notifications')->count();
        $this->assertGreaterThan(0, $count);
        $this->post(route('articles.approve', $article))->assertRedirect();
        $this->assertDatabaseCount('notifications', $count);
    }

    public function test_store_ignores_forged_workflow_fields_and_edit_requires_review_again(): void
    {
        $owner = $this->actor('Multimedia');
        $president = $this->actor('President');
        $this->actingAs($owner)->post(route('articles.store'), [
            'title' => 'Submitted title', 'content' => '<script>alert(1)</script>',
            'approval_status' => 'approved', 'created_by_user_id' => $president->user_id,
        ])->assertRedirect();
        $article = Article::firstOrFail();
        $this->assertSame($owner->user_id, $article->created_by_user_id);
        $this->assertSame('pending', $article->approval_status);
        $this->get(route('workspace.posts.articles.show', $article))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->actingAs($president)->post(route('articles.approve', $article))->assertRedirect();
        $this->actingAs($owner)->patch(route('articles.update', $article), ['title' => 'Edited title', 'content' => 'Updated content'])->assertRedirect(route('workspace.posts.articles.show', $article));
        $this->assertSame('pending', $article->fresh()->approval_status);
    }

    public function test_pdf_validation_storage_and_replacement_preserve_private_delivery(): void
    {
        $secretary = $this->actor('Secretary');
        $this->actingAs($secretary)->post(route('documents.store'), [
            'title' => 'Invalid PDF', 'file' => UploadedFile::fake()->createWithContent('fake.pdf', '<html>not a pdf</html>'),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
        $this->post(route('documents.store'), [
            'title' => 'Valid PDF', 'semester' => 1,
            'file' => UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $document = Document::firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertSame('pending', $document->approval_status);
        $oldPath = $document->file_path;
        $this->patch(route('documents.update', $document), ['title' => 'Metadata only', 'semester' => 2])->assertSessionHasNoErrors();
        $this->assertSame($oldPath, $document->fresh()->file_path);
        Storage::disk('local')->assertExists($oldPath);
    }
}
