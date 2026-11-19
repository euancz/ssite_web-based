<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MobileParityTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function signIn(): User
    {
        $user = User::forceCreate(['name' => 'Student', 'email' => 'student@example.test', 'role' => 'student', 'profile_completed_at' => now()]);
        Cache::put('mobile_access:parity', $user->getKey(), now()->addHour());
        $this->withToken(Crypt::encryptString(json_encode(['sub' => $user->getKey(), 'jti' => 'parity', 'exp' => now()->addHour()->timestamp])));
        return $user;
    }

    public function test_account_pages_require_authentication_and_only_allow_fixed_destinations(): void
    {
        $this->postJson('/api/mobile/v1/account/session', ['destination' => 'profile'])->assertUnauthorized();
        $this->getJson('/api/mobile/v1/discover')->assertUnauthorized();
        $this->signIn();
        foreach (['https://example.com', '/adviser/users', 'workspace'] as $destination) {
            $this->postJson('/api/mobile/v1/account/session', compact('destination'))->assertUnprocessable();
        }
        $this->postJson('/api/mobile/v1/workspace/session')->assertForbidden();
    }

    public function test_students_open_their_own_account_pages_with_single_use_handoffs(): void
    {
        $user = $this->signIn();
        foreach (\App\Support\MobileAccountSession::DESTINATIONS as $destination => $route) {
            $url = $this->postJson('/api/mobile/v1/account/session', compact('destination'))->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')->json('url');
            $this->get($url)->assertRedirect(route($route))->assertHeader('Referrer-Policy', 'no-referrer');
            $this->assertAuthenticatedAs($user);
            $this->get($url)->assertUnauthorized();
        }
    }

    public function test_expiry_revocation_and_profile_completion_are_rechecked(): void
    {
        $user = $this->signIn();
        $issue = fn () => $this->postJson('/api/mobile/v1/account/session', ['destination' => 'profile'])->assertOk()->json('url');
        $url = $issue();
        $this->travel(61)->seconds();
        $this->get($url)->assertUnauthorized();
        $this->travelBack();
        $url = $issue();
        Cache::forget('mobile_access:parity');
        $this->get($url)->assertUnauthorized();
        Cache::put('mobile_access:parity', $user->getKey(), now()->addHour());
        $url = $issue();
        $user->forceFill(['profile_completed_at' => null])->save();
        $this->get($url)->assertForbidden();
    }

    public function test_mobile_discover_uses_the_web_selection_and_article_renderer(): void
    {
        $user = $this->signIn();
        (require database_path('migrations/2026_10_27_000004_add_content_format_to_articles_table.php'))->up();
        foreach (['approved', 'pending'] as $status) {
            Article::forceCreate(['title' => $status, 'content' => "## Heading\n\n**Bold** [Link](https://example.com)\n\n<script>unsafe</script>",
                'content_format' => 'markdown', 'created_by_user_id' => $user->getKey(), 'approval_status' => $status, 'content_status' => 'active']);
        }
        $web = app(\App\Http\Controllers\DiscoverController::class)(request())->getData();
        $response = $this->getJson('/api/mobile/v1/discover')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($web['articles']->modelKeys(), array_column($response->json('data.articles'), 'id'));
        $response->assertJsonCount(1, 'data.articles');
        $article = $web['articles']->first();
        $html = $response->json('data.articles.0.content_html');
        $this->assertSame(app(\App\Services\ArticleContent::class)->html($article->content, 'markdown'), $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('**', $response->json('data.articles.0.description'));

        $this->getJson('/api/mobile/v1/discover?feed=1&q=approved&period=week&_suggest=1', [
            'Sec-Fetch-Dest' => 'document',
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $article->id)
            ->assertJsonPath('meta.total', 1);
    }
}
