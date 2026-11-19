<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateMobileToken;
use App\Models\Article;
use App\Models\ContentDraft;
use App\Models\User;
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

    public function test_home_and_discover_show_latest_articles_without_the_legacy_column(): void
    {
        $owner = $this->actor();
        $article = $this->article($owner);

        $this->actingAs($owner);
        $this->assertSame([$article->getKey()], $this->get(route('home'))->assertOk()->viewData('articles')->modelKeys());
        $this->assertSame([$article->getKey()], $this->get(route('discover'))->assertOk()->viewData('articles')->modelKeys());
    }

    public function test_published_articles_have_no_manual_feature_control_or_route(): void
    {
        $this->enable();
        $owner = $this->actor();
        $article = $this->article($owner, ['featured_at' => '2026-10-20 12:00:00']);
        $this->actingAs($owner)->get(route('workspace.posts.articles.show', $article))
            ->assertOk()->assertDontSee('Featured article')->assertDontSee('Remove featured selection');
        $this->patch(route('workspace.posts.articles.show', $article).'/featured', ['featured' => false])
            ->assertNotFound();
        $this->assertSame('2026-10-20 12:00:00', $article->fresh()->getRawOriginal('featured_at'));
    }

    public function test_home_and_discover_order_public_articles_by_creation_time(): void
    {
        $this->enable();
        $owner = $this->actor();
        $older = $this->article($owner, ['title' => 'Older selected article', 'featured_at' => '2026-10-30 12:00:00']);
        $recent = $this->article($owner, ['title' => 'Recent article', 'created_at' => '2026-10-26 12:00:00']);
        $latest = $this->article($owner, ['title' => 'Latest article', 'created_at' => '2026-10-27 12:00:00']);
        foreach ([['approval_status' => 'pending'], ['approval_status' => 'rejected'], ['content_status' => 'archived']] as $state) {
            $this->article($owner, $state + ['title' => 'Private selected article',
                'created_at' => '2026-10-31 12:00:00', 'featured_at' => '2026-10-31 12:00:00']);
        }

        $home = $this->actingAs($this->actor('', 'student'))->get(route('home'))->assertOk();
        $this->assertSame([$latest->getKey()], $home->viewData('articles')->modelKeys());

        $discover = $this->get(route('discover'))->assertOk()->assertDontSee('Featured first');
        $this->assertSame('recent', $discover->viewData('filters')['sort']);
        $latestFirst = [$latest->getKey(), $recent->getKey(), $older->getKey()];
        $this->assertSame($latestFirst, $discover->viewData('articles')->modelKeys());

        $legacy = $this->get(route('discover', ['sort' => 'featured']))->assertOk();
        $this->assertSame($latestFirst, $legacy->viewData('articles')->modelKeys());

        $oldest = $this->get(route('discover', ['sort' => 'oldest']))->assertOk();
        $this->assertSame(array_reverse($latestFirst), $oldest->viewData('articles')->modelKeys());

        $this->withoutMiddleware(AuthenticateMobileToken::class);
        $this->getJson(route('api.mobile.home'))->assertOk()->assertJsonPath('data.articles.0.id', $latest->getKey());
        $this->getJson(route('api.mobile.discover'))->assertOk()->assertJsonPath('data.articles.*.id', $latestFirst);
    }

    public function test_article_writes_and_drafts_ignore_forged_feature_selection(): void
    {
        $this->enable();
        $owner = $this->actor();
        $article = $this->article($owner, ['image' => 'post-media/articles/existing.png']);
        $this->actingAs($owner)->patch(route('articles.update', $article), [
            'title' => 'Edited title', 'content' => 'Body', 'featured_at' => '2026-10-27 12:00:00',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($article->fresh()->featured_at);
        $this->post(route('workspace.drafts.save', 'articles'), ['title' => 'Draft', 'featured_at' => '2026-10-27 12:00:00'])->assertRedirect();
        $this->assertArrayNotHasKey('featured_at', ContentDraft::firstOrFail()->payload);
    }
}
