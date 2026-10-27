<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ArticleCategoryTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        (require database_path('migrations/2026_10_27_000007_add_category_to_articles_table.php'))->up();
    }

    private function actor(): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Editor', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer', 'officer_position' => 'Multimedia', 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function article(User $owner, string $title, ?string $category, string $approval = 'pending'): Article
    {
        $post = new Article(['title' => $title, 'category' => $category, 'content' => 'Article text']);
        $post->forceFill(['created_by_user_id' => $owner->getKey(), 'approval_status' => $approval, 'content_status' => 'active'])->save();

        return $post;
    }

    public function test_public_filter_excludes_private_categories_and_validates_input(): void
    {
        $owner = $this->actor();
        $this->article($owner, 'Published project', 'Projects', 'approved');
        $this->article($owner, 'Uncategorized legacy', null, 'approved');
        $this->article($owner, 'Private project', 'Private category');
        $archived = $this->article($owner, 'Archived article', 'Archived category', 'approved');
        $archived->content_status = 'archived';$archived->save();
        $response = $this->get(route('articles.index', ['category' => 'Projects']))->assertOk()
            ->assertSee('Published project')->assertDontSee('Uncategorized legacy')->assertDontSee('Private category')->assertDontSee('Archived category');
        $this->assertSame(['' => 'All categories', 'Projects' => 'Projects'], $response->viewData('categoryOptions'));
        $this->get(route('articles.index'))->assertOk()->assertSee('Uncategorized legacy');
        $this->getJson(route('articles.index', ['category' => ['bad']]))->assertUnprocessable();
        $this->getJson(route('articles.index', ['category' => str_repeat('x', 101)]))->assertUnprocessable();
    }

    public function test_workspace_filter_combines_search_and_preserves_pagination_without_other_owners(): void
    {
        $owner = $this->actor();
        for ($i=0;$i<6;$i++) $this->article($owner, 'Project '.$i, 'Projects');
        $this->article($owner, 'Different topic', 'News');
        $this->article($this->actor(), 'Other owner', 'Hidden category');
        $response = $this->actingAs($owner)->get(route('workspace.posts.articles.index', ['tab'=>'mine','category'=>'Projects','q'=>'Project','sort'=>'title']))->assertOk()->assertDontSee('Hidden category');
        $this->assertSame(6, $response->viewData('articles')->total());
        $this->assertStringContainsString('category=Projects', $response->viewData('articles')->nextPageUrl());
    }

    public function test_drafts_and_edits_preserve_categories_and_edits_require_review(): void
    {
        (require database_path('migrations/2026_10_27_000006_create_content_versions_table.php'))->up();
        $owner = $this->actor();$this->actingAs($owner);
        $this->post(route('workspace.drafts.save', 'articles'), ['title'=>'Draft', 'category'=>'Projects'])->assertRedirect();
        $draft = ContentDraft::firstOrFail();
        $this->assertSame('Projects', $draft->payload['category']);
        $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertSee('value="Projects"', false);
        $post = $this->article($owner, 'Approved article', null, 'approved');
        $this->patch(route('articles.update', $post), ['title'=>$post->title,'content'=>$post->content,'category'=>'<b>Projects</b>'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('pending', $post->fresh()->approval_status);
        $snapshot = json_decode(DB::table('content_versions')->orderByDesc('id')->value('snapshot'), true);
        $this->assertSame('<b>Projects</b>', $snapshot['category']);
        $this->get(route('workspace.posts.articles.show', $post))->assertOk()->assertSee('&lt;b&gt;Projects&lt;/b&gt;', false)->assertDontSee('<b>Projects</b>', false);
        $this->patch(route('articles.update', $post), ['title'=>$post->title,'content'=>$post->content,'category'=>str_repeat('x',101)])->assertSessionHasErrors('category');
        $this->patch(route('articles.update', $post), ['title'=>$post->title,'content'=>$post->content,'category'=>''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($post->fresh()->category);
        $this->actingAs($this->actor())->patch(route('articles.update', $post), ['title'=>'Forged','content'=>'text','category'=>'Projects'])->assertForbidden();
    }

    public function test_missing_column_keeps_forms_and_writes_compatible(): void
    {
        Schema::table('articles', fn ($table) => $table->dropColumn('category'));
        $this->actingAs($this->actor())->get(route('articles.create'))->assertOk()->assertDontSee('id="article-category"', false);
        $this->post(route('articles.store'), ['title'=>'Legacy schema','content'=>'Text','category'=>'Ignored'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['title'=>'Legacy schema']);
    }
}
