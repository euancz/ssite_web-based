<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ReviewHistoryBrowseTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-27 18:00:00'));

    }

    private function enable(): void
    {
        (require database_path('migrations/2026_10_27_000005_create_content_review_events_table.php'))->up();
    }

    private function actor(string $position = 'Social Media Manager', string $role = 'officer'): User
    {
        $user = new User;
        $user->forceFill(['name' => $position, 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function record(User $owner, string $class = Article::class): Model
    {
        $post = new $class;
        $data = ['title' => 'Current title', 'created_by_user_id' => $owner->getKey(), 'approval_status' => 'approved', 'content_status' => 'active'];
        if ($class === Document::class) {
            $data += ['file_path' => 'private.pdf', 'original_name' => 'report.pdf'];
        } else {
            $data['content'] = 'Content';
        }
        $post->forceFill($data)->save();

        return $post;
    }

    private function event(Model $post, string $title, array $values = []): int
    {
        return DB::table('content_review_events')->insertGetId($values + [
            'post_type' => $post::POST_TYPE, 'post_id' => $post->getKey(), 'post_title' => $title,
            'reviewer_user_id' => 999, 'reviewer_name' => 'Former reviewer', 'decision' => 'approved',
            'reason' => null, 'created_at' => '2026-10-27 12:00:00',
        ]);
    }

    public function test_access_requires_a_current_content_capability_and_installed_history(): void
    {
        $this->get(route('workspace.content-activity.index'))->assertRedirect();
        $this->actingAs($this->actor())->get(route('workspace.content-activity.index'))->assertOk()->assertSee('Review tracking is not available');
        $this->enable();
        foreach (['student', 'adviser', 'admin'] as $role) {
            $this->actingAs($this->actor('President', $role))->get(route('workspace.content-activity.index'))->assertForbidden();
        }
        $this->actingAs($this->actor())->get(route('workspace.content-activity.index'))->assertOk()->assertSee('No activity matches')->assertSee('workspace-content', false);
    }

    public function test_author_scope_prevents_cross_owner_cross_module_and_deleted_post_disclosure(): void
    {
        $this->enable();
        $owner = $this->actor();
        $mine = $this->record($owner);
        $this->event($mine, 'My prior title');
        $other = $this->record($this->actor());
        $this->event($other, 'Another author title');
        $this->event($this->record($owner, Document::class), 'Lost module title');
        $deleted = $this->record($owner);
        $this->event($deleted, 'Deleted post title');
        $deleted->delete();
        $response = $this->actingAs($owner)->get(route('workspace.content-activity.index'))->assertOk()
            ->assertSee('My prior title')->assertDontSee('Another author title')->assertDontSee('Lost module title')->assertDontSee('Deleted post title');
        $this->assertSame(2, $response->viewData('log')['events']->total());
        $this->getJson(route('workspace.content-activity.index', ['q' => 'tITLE', 'action' => 'approved', '_suggest' => 1]))
            ->assertOk()->assertExactJson(['results' => ['My prior title']]);
        $this->get(route('workspace.content-activity.index', ['module' => 'articles', 'post' => $other->getKey()]))->assertOk()->assertDontSee('Another author title');
        $owner->officer_position = 'Secretary';
        $owner->save();
        $this->get(route('workspace.content-activity.index'))->assertOk()->assertDontSee('My prior title')->assertSee('Lost module title');
    }

    public function test_president_filters_combine_and_preserve_pagination_with_stable_order(): void
    {
        $this->enable();
        $article = $this->record($this->actor());
        $ids = [];
        for ($i = 0; $i < 27; $i++) {
            $ids[] = $this->event($article, 'Report '.$i, ['decision' => 'rejected', 'reason' => 'Add sources']);
        }
        $this->event($article, 'Different decision');
        $this->event($article, 'Report outside date', ['created_at' => '2026-10-28 00:00:00', 'decision' => 'rejected']);
        $this->event($this->record($this->actor('Secretary'), Document::class), 'Report other module', ['decision' => 'rejected']);
        $filters = ['q' => 'Report', 'module' => 'articles', 'action' => 'rejected', 'from' => '2026-10-27', 'to' => '2026-10-27', 'sort' => 'oldest'];
        $response = $this->actingAs($this->actor('President'))->get(route('workspace.content-activity.index', $filters))->assertOk();
        $events = $response->viewData('log')['events'];
        $this->assertSame(27, $events->total());
        $this->assertSame(array_slice($ids, 0, 25), $events->pluck('event_id')->all());
        foreach (['module=articles', 'q=Report', 'action=rejected', 'to=2026-10-27', 'sort=oldest'] as $retained) {
            $this->assertStringContainsString($retained, $events->nextPageUrl());
        }
        $pageTwo = $this->get(route('workspace.content-activity.index', $filters + ['page' => 2]))->assertOk();
        $this->assertSame(array_slice($ids, 25), $pageTwo->viewData('log')['events']->pluck('event_id')->all());
        $response->assertDontSee('Report outside date')->assertDontSee('Report other module');
    }

    public function test_malformed_filters_are_rejected_without_broadening_scope(): void
    {
        $this->enable();
        $this->actingAs($this->actor());
        foreach ([['module' => 'documents'], ['q' => ['bad']], ['action' => 'pending'], ['sort' => 'id desc'], ['post' => 1], ['from' => '2026-10-28', 'to' => '2026-10-27'], ['from' => '2026-02-30']] as $filters) {
            $this->getJson(route('workspace.content-activity.index', $filters))->assertUnprocessable();
        }
    }

    public function test_snapshot_values_are_escaped_and_post_link_uses_current_workspace_route(): void
    {
        $this->enable();
        $owner = $this->actor();
        $article = $this->record($owner);
        $this->event($article, '<script>Old title</script>', ['decision' => 'rejected', 'reason' => '<img src=x onerror=alert(1)>', 'reviewer_name' => '<svg onload=alert(1)>']);
        $this->actingAs($owner)->get(route('workspace.content-activity.index'))->assertOk()
            ->assertSee('&lt;script&gt;Old title&lt;/script&gt;', false)->assertDontSee('<script>Old title</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)->assertDontSee('<svg onload=alert(1)>', false)
            ->assertSee(route('workspace.posts.articles.show', $article));
        $activityUrl = route('workspace.content-activity.index', ['module' => 'articles', 'post' => $article->getKey()]);
        $this->get(route('workspace.posts.articles.show', $article))->assertOk()->assertDontSee($activityUrl);
        $article->forceFill(['approval_status' => 'pending'])->save();
        $this->get(route('workspace.posts.articles.show', $article))->assertOk()->assertSee($activityUrl);
    }
}
