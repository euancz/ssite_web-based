<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentDraft;
use App\Models\User;
use App\Services\ArticleContent;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ArticleFormattingTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function enable(): void
    {
        (require database_path('migrations/2026_10_27_000004_add_content_format_to_articles_table.php'))->up();
    }

    private function author(string $position = 'President', string $role = 'officer'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Author', 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function article(User $author, array $values = []): Article
    {
        $article = new Article;
        $article->forceFill($values + ['title' => 'Formatted article', 'content' => '**Legacy text** <script>alert(1)</script>',
            'created_by_user_id' => $author->getKey(), 'approval_status' => 'approved', 'content_status' => 'active'])->save();

        return $article;
    }

    public function test_migration_keeps_legacy_content_plain_and_pre_migration_workflows_available(): void
    {
        $author = $this->author();
        $article = $this->article($author);
        $before = DB::table('articles')->first();
        $this->actingAs($author)->get(route('articles.create'))->assertOk()->assertDontSee('name="content_format"', false);
        $this->postJson(route('workspace.articles.preview'), ['content' => 'Text', 'content_format' => 'markdown'])->assertNotFound();
        $this->patch(route('articles.update', $article), ['title' => $article->title, 'content' => $article->content, 'content_format' => 'markdown'])->assertSessionHasNoErrors();
        $this->enable();
        $this->assertSame('plain', $article->fresh()->content_format);
        $this->assertSame($before->content, $article->fresh()->content);
        // The edit returns content to review; restore publication in this isolated fixture to inspect rendering.
        $article->refresh()->forceFill(['approval_status' => 'approved'])->save();
        $this->get(route('articles.show', $article))->assertOk()->assertSee('**Legacy text**')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<strong>Legacy text</strong>', false);
    }

    public function test_renderer_allows_formatting_but_never_raw_html_unsafe_links_or_remote_images(): void
    {
        $renderer = app(ArticleContent::class);
        $html = $renderer->html("## Heading\n\n**Bold** *Italic*\n\n- First\n- Second\n\n[Safe](https://example.com)\n\n![No tracking](https://example.com/pixel.png)\n\n<script>alert(1)</script><img src=x onerror=alert(1)>\n\n[Unsafe](javascript:alert%281%29) [Encoded](jav&#x61;script:alert%281%29) [File](file:///etc/passwd) [Relative](//example.com)", 'markdown');
        $this->assertStringContainsString('<h2>Heading</h2>', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('<em>Italic</em>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $this->assertSame(0, $dom->getElementsByTagName('script')->length);
        $this->assertSame(0, $dom->getElementsByTagName('img')->length);
        $this->assertSame(1, $dom->getElementsByTagName('a')->length);
        $this->assertSame('https://example.com', $dom->getElementsByTagName('a')->item(0)->getAttribute('href'));
        $this->assertStringNotContainsString('<svg', $renderer->html('<svg onload=alert(1)>', 'plain'));
        $this->assertStringNotContainsString('**', $renderer->text('**Readable** summary', 'markdown'));
    }

    public function test_preview_requires_module_permission_is_bounded_private_and_does_not_persist(): void
    {
        $this->enable();
        $url = route('workspace.articles.preview');
        $payload = ['content' => '**Unsaved**', 'content_format' => 'markdown'];
        $this->postJson($url, $payload)->assertUnauthorized();
        $this->actingAs($this->author('', 'student'))->postJson($url, $payload)->assertForbidden();
        $this->actingAs($this->author())->postJson($url, $payload)->assertOk()->assertJson(['html' => "<p><strong>Unsaved</strong></p>\n"])->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson($url, ['content' => str_repeat('a', 20001), 'content_format' => 'markdown'])->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->postJson($url, ['content' => [], 'content_format' => 'html'])->assertUnprocessable()->assertJsonValidationErrors(['content', 'content_format']);
        $this->assertSame(0, Article::count());
        $this->assertSame(0, ContentDraft::count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_format_survives_draft_resume_submission_and_omitted_format_on_edit(): void
    {
        $this->enable();
        $this->actingAs($this->author());
        $this->post(route('workspace.drafts.save', 'articles'), ['title' => 'Formatted draft', 'content' => '**Saved** body', 'content_format' => 'markdown'])->assertSessionHasNoErrors()->assertRedirect();
        $draft = ContentDraft::firstOrFail();
        $this->assertSame('markdown', $draft->payload['content_format']);
        $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertSee('value="markdown" selected', false)->assertSee('**Saved** body');
        $this->post(route('articles.store'), ['title' => 'Formatted draft', 'content' => '**Saved** body', 'content_format' => 'markdown', 'draft_id' => $draft->getKey(), 'draft_version' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $article = Article::firstOrFail();
        $this->assertSame('markdown', $article->content_format);
        $this->assertSame(0, ContentDraft::count());
        $this->get(route('articles.show', $article))->assertOk()->assertSee('<strong>Saved</strong>', false);
        $this->patch(route('articles.update', $article), ['title' => 'Edited', 'content' => '**Still formatted**'])->assertSessionHasNoErrors();
        $this->assertSame('markdown', $article->fresh()->content_format);
        $this->assertSame('pending', $article->fresh()->approval_status);
        $this->patch(route('articles.update', $article), ['title' => 'Plain', 'content' => '**Now literal**', 'content_format' => 'plain'])->assertSessionHasNoErrors();
        $this->assertSame('plain', $article->fresh()->content_format);
    }

    public function test_word_limit_and_invalid_formats_remain_enforced(): void
    {
        $this->enable();
        $this->actingAs($this->author());
        $this->post(route('articles.store'), ['title' => 'Long', 'content' => implode(' ', array_fill(0, 301, '**word**')), 'content_format' => 'markdown'])->assertSessionHasErrors('content');
        $this->post(route('articles.store'), ['title' => 'Invalid', 'content' => 'Body', 'content_format' => 'html'])->assertSessionHasErrors('content_format');
        $this->post(route('articles.store'), ['title' => 'Oversized', 'content' => str_repeat('a', 20001), 'content_format' => 'markdown'])->assertSessionHasErrors('content');
        $this->assertSame(0, Article::count());
    }

    public function test_lists_discover_and_search_use_plain_excerpts_and_keep_private_boundaries(): void
    {
        $this->enable();
        $author = $this->author();
        $article = $this->article($author, ['content_format' => 'markdown', 'content' => '**Readable** summary']);
        $private = $this->article($author, ['title' => 'Private readable', 'content_format' => 'markdown', 'content' => '**Private** body', 'approval_status' => 'pending']);
        $this->get(route('articles.index'))->assertOk()->assertSee('Readable summary')->assertDontSee('**Readable**')->assertDontSee('Private readable');
        $this->get(route('discover'))->assertOk()->assertSee('Readable summary')->assertDontSee('**Readable**');
        $this->getJson(route('search.suggest', ['q' => 'Readable']))->assertOk()->assertJsonCount(1, 'results')->assertDontSee('**Readable**')->assertDontSee('Private readable');
        $this->get(route('articles.show', $private))->assertNotFound();
    }
}
