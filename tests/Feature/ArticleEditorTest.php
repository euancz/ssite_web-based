<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Article;
use App\Services\ArticleContent;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;
class ArticleEditorTest extends TestCase
{
    use BuildsContentSchema;
    protected function setUp(): void { parent::setUp(); $this->buildContentSchema(); (require database_path('migrations/2026_10_27_000004_add_content_format_to_articles_table.php'))->up(); }
    public function test_extended_formatting_survives_preview_save_and_edit(): void
    {
        $user = new User;
        $user->forceFill(['name'=>'Editor', 'email'=>'editor@mcc.edu.ph', 'role'=>'officer', 'officer_position'=>'President', 'profile_completed_at'=>now()])->save();
        $source = "### Schedule\n\n~~Old~~ **New**\n\n| Day | Event |\n| --- | --- |\n| Monday | [Details](https://example.com) |\n\n---\n\n```text\n<script>alert(1)</script>\n```";
        $html = app(ArticleContent::class)->html($source, 'markdown');
        foreach (['<h3>Schedule</h3>', '<del>Old</del>', '<table>', '<th>Day</th>', '<hr', '<pre><code', '&lt;script&gt;'] as $markup) $this->assertStringContainsString($markup, $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->actingAs($user)->postJson(route('workspace.articles.preview'), ['content'=>$source, 'content_format'=>'markdown'])->assertOk()->assertJson(['html'=>$html]);
        $this->post(route('articles.store'), ['title'=>'Schedule', 'content'=>$source, 'content_format'=>'markdown'])->assertSessionHasNoErrors()->assertRedirect();
        $article=Article::firstOrFail();
        $this->assertSame($source, $article->content);
        $this->assertSame($html, app(ArticleContent::class)->html($article->content, $article->content_format));
        $this->get(route('articles.edit', $article))->assertOk()->assertSee(e($source), false);
    }
    public function test_extended_table_content_keeps_links_and_html_restricted(): void
    {
        $html=app(ArticleContent::class)->html("| Name | Link |\n| --- | --- |\n| ~~Old~~ | [Bad](javascript:alert%281%29) |\n| <script>alert(1)</script> | ![Image](https://example.com/pixel.png) |", 'markdown');
        $dom=new \DOMDocument; @$dom->loadHTML($html);
        foreach (['script','img','a'] as $tag) $this->assertSame(0, $dom->getElementsByTagName($tag)->length);
        $this->assertSame(1, $dom->getElementsByTagName('table')->length);
    }
}
