<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentDraft;
use App\Models\User;
use App\Services\FeaturedArticles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class FeaturedArticleTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function enable(): void
    {
        (require database_path('migrations/2026_10_27_000003_add_featured_at_to_articles_table.php'))->up();
    }

    private function actor(string $position = 'President', string $role = 'officer'): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Editor', 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function article(User $owner, array $values = []): Article
    {
        $article = new Article;
        $article->forceFill(array_merge(['title' => 'Article fixture', 'content' => 'Article body',
            'created_by_user_id' => $owner->getKey(), 'approval_status' => 'approved', 'content_status' => 'active',
            'created_at' => '2026-01-01 12:00:00', 'updated_at' => '2026-01-01 12:00:00'], $values))->save();

        return $article;
    }

    private function select(Article $article, bool $selected, ?string $expected = null): TestResponse
    {
        return $this->patch(route('workspace.posts.articles.featured', $article), [
            'featured' => $selected, 'expected_featured_at' => $expected,
        ]);
    }

    public function test_migration_is_additive_and_pre_migration_pages_keep_recent_fallback(): void
    {
        $owner = $this->actor();
        $article = $this->article($owner);
        $before = DB::table('articles')->first();
        $this->get('/')->assertOk();
        $this->assertSame($article->getKey(), app(FeaturedArticles::class)->forDisplay(1)->first()->getKey());
        $this->actingAs($owner)->get(route('workspace.posts.articles.show', $article))->assertOk()->assertDontSee('Feature on Home and Discover');
        $this->select($article, true)->assertNotFound();
        $this->enable();
        $this->assertNull($article->fresh()->featured_at);
        $this->assertTrue(collect(Schema::getIndexes('articles'))->contains(fn ($index) => $index['name'] === 'articles_featured_at_index'));
        (require database_path('migrations/2026_10_27_000003_add_featured_at_to_articles_table.php'))->down();
        $this->assertEquals($before, DB::table('articles')->first());
    }

    public function test_only_president_can_change_features_and_public_pages_never_show_controls(): void
    {
        $this->enable();
        $president = $this->actor();
        $article = $this->article($president);
        $this->select($article, true)->assertRedirect();
        foreach ([['Secretary', 'officer'], ['President', 'student'], ['', 'adviser'], ['', 'superadmin']] as [$position, $role]) {
            $this->actingAs($this->actor($position, $role));
            $this->select($article, true)->assertForbidden();
        }
        $this->actingAs($president)->get(route('articles.show', $article))->assertOk()->assertDontSee('Feature on Home and Discover');
        $this->get(route('workspace.posts.articles.show', $article))->assertOk()->assertSee('Feature on Home and Discover');
        $this->assertNull($article->fresh()->featured_at);
    }

    public function test_feature_and_remove_preserve_content_review_timestamps_and_send_no_notifications(): void
    {
        $this->enable();
        $president = $this->actor();
        $article = $this->article($president, ['reviewed_by_user_id' => $president->getKey(), 'reviewed_at' => '2026-01-01 12:00:00']);
        $before = (array) DB::table('articles')->first();
        $this->actingAs($president);
        $this->select($article, true)->assertSessionHasNoErrors()->assertRedirect(route('workspace.posts.articles.show', $article));
        $selectedAt = $article->fresh()->getRawOriginal('featured_at');
        $this->assertNotNull($selectedAt);
        $after = (array) DB::table('articles')->first();
        unset($before['featured_at'], $after['featured_at']);
        $this->assertSame($before, $after);
        $this->select($article, false)->assertSessionHasErrors('featured');
        $this->assertSame($selectedAt, $article->fresh()->getRawOriginal('featured_at'));
        $this->travel(2)->seconds();
        $this->select($article, true, $selectedAt)->assertSessionHasNoErrors();
        $this->assertSame($selectedAt, $article->fresh()->getRawOriginal('featured_at'));
        $this->select($article, false, $selectedAt)->assertSessionHasNoErrors();
        $this->assertNull($article->fresh()->featured_at);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_only_published_content_can_be_selected_and_hidden_selections_can_be_removed(): void
    {
        $this->enable();
        $owner = $this->actor();
        $this->actingAs($owner);
        foreach ([['approval_status' => 'pending'], ['approval_status' => 'rejected'], ['content_status' => 'archived']] as $state) {
            $article = $this->article($owner, $state);
            $this->select($article, true)->assertSessionHasErrors('featured');
            $this->assertNull($article->fresh()->featured_at);
        }
        $article->forceFill(['featured_at' => '2026-01-01 12:00:00'])->save();
        $this->select($article, false, '2026-01-01 12:00:00')->assertSessionHasNoErrors();
        $this->assertNull($article->fresh()->featured_at);
        $this->patch(route('workspace.posts.articles.featured', $article), ['featured' => 'yes'])->assertSessionHasErrors(['featured', 'expected_featured_at']);
    }

    public function test_home_and_discover_prioritize_selected_public_articles_and_fill_with_recent_articles(): void
    {
        $this->enable();
        $owner = $this->actor();
        $older = $this->article($owner, ['title' => 'Selected older article', 'featured_at' => '2026-10-20 12:00:00']);
        $priority = $this->article($owner, ['title' => 'Most recently selected', 'featured_at' => '2026-10-21 12:00:00']);
        $recent = $this->article($owner, ['title' => 'Recent fallback', 'created_at' => '2026-10-26 12:00:00']);
        foreach ([['approval_status' => 'pending'], ['approval_status' => 'rejected'], ['content_status' => 'archived']] as $state) {
            $this->article($owner, $state + ['title' => 'Private selected article', 'featured_at' => '2026-10-27 12:00:00']);
        }
        $home = $this->get('/')->assertOk()->assertDontSee('Private selected article');
        $this->assertSame([$priority->getKey()], $home->viewData('articles')->modelKeys());
        $discover = $this->get(route('discover'))->assertOk()->assertDontSee('Private selected article');
        $this->assertSame([$priority->getKey(), $older->getKey(), $recent->getKey()], $discover->viewData('articles')->modelKeys());
        // Existing list sorts retain their meaning; featured priority is limited to the showcase slots.
        $list = $this->get(route('articles.index', ['sort' => 'recent']))->assertOk();
        $this->assertSame($recent->getKey(), $list->viewData('articles')->first()->getKey());
        $priority->forceFill(['approval_status' => 'pending'])->save();
        $this->assertSame($older->getKey(), app(FeaturedArticles::class)->forDisplay(1)->first()->getKey());
    }

    public function test_article_writes_and_drafts_ignore_forged_feature_selection(): void
    {
        $this->enable();
        $owner = $this->actor();
        $article = $this->article($owner);
        $this->actingAs($owner)->patch(route('articles.update', $article), [
            'title' => 'Edited title', 'content' => 'Body', 'featured_at' => '2026-10-27 12:00:00',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($article->fresh()->featured_at);
        $this->post(route('workspace.drafts.save', 'articles'), ['title' => 'Draft', 'featured_at' => '2026-10-27 12:00:00'])->assertRedirect();
        $this->assertArrayNotHasKey('featured_at', ContentDraft::firstOrFail()->payload);
    }
}
