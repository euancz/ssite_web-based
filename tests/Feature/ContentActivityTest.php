<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use App\Services\ContentActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentActivityTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->travelTo(Carbon::parse('2026-10-27 12:00:00'));
    }

    private function enableHistory(): void
    {
        (require database_path('migrations/2026_10_27_000005_create_content_review_events_table.php'))->up();
    }

    private function actor(string $position = 'Multimedia', string $role = 'officer'): User
    {
        $user = new User;
        $user->forceFill(['name' => $position, 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function record(User $owner, string $date, array $values = [], string $class = Article::class): Model
    {
        $post = new $class;
        $data = $values + ['title' => 'Fixture', 'created_by_user_id' => $owner->getKey(),
            'created_at' => $date, 'updated_at' => $date, 'approval_status' => 'pending', 'content_status' => 'active'];
        if ($class === Document::class) {
            $data += ['file_path' => 'private.pdf', 'original_name' => 'fixture.pdf'];
        } else {
            $data['content'] = 'Body';
        }
        $post->forceFill($data)->save();

        return $post;
    }

    private function event(Model $post, string $decision, string $date): void
    {
        DB::table('content_review_events')->insert(['post_type' => $post::POST_TYPE, 'post_id' => $post->getKey(),
            'reviewer_user_id' => 999, 'reviewer_name' => 'Reviewer', 'post_title' => 'Snapshot', 'decision' => $decision,
            'reason' => $decision === 'rejected' ? 'Add references' : null, 'created_at' => $date]);
    }

    public function test_submissions_use_original_creation_dates_current_scope_and_exact_window(): void
    {
        $owner = $this->actor();
        $this->record($owner, '2026-10-21 00:00:00');
        $this->record($owner, '2026-10-27 12:00:00', ['approval_status' => 'rejected', 'content_status' => 'archived']);
        $old = $this->record($owner, '2026-10-20 23:59:59');
        $old->forceFill(['updated_at' => now()])->save();
        $this->record($owner, '2026-10-27 12:00:01');
        $this->record($this->actor(), '2026-10-25 12:00:00');
        $this->record($owner, '2026-10-25 12:00:00', [], Document::class);
        $report = app(ContentActivity::class)->forUser($owner, 7);
        $this->assertCount(7, $report['daily']);
        $this->assertSame(2, $report['totals']['submissions']);
        $this->assertSame(1, $report['daily']['2026-10-21']['submissions']);
        $this->assertSame(0, $report['daily']['2026-10-25']['submissions']);
        $this->assertFalse($report['historyAvailable']);
        $this->actingAs($owner)->get(route('workspace.content-activity.index'))->assertOk()->assertSee('Review tracking is not available')->assertSee('workspace-content', false);
    }

    public function test_decisions_count_events_not_unique_posts_and_exclude_orphan_and_private_events(): void
    {
        $this->enableHistory();
        $owner = $this->actor();
        $post = $this->record($owner, '2026-01-01 12:00:00');
        $this->event($post, 'rejected', '2026-10-21 00:00:00');
        $this->event($post, 'approved', '2026-10-27 11:00:00');
        $this->event($post, 'approved', '2026-10-27 13:00:00');
        $this->event($post, 'rejected', '2026-10-20 23:59:59');
        $other = $this->record($this->actor(), '2026-10-25 12:00:00');
        $this->event($other, 'approved', '2026-10-26 12:00:00');
        $deleted = $this->record($owner, '2026-10-25 12:00:00');
        $this->event($deleted, 'approved', '2026-10-26 12:00:00');
        $deleted->delete();
        $report = app(ContentActivity::class)->forUser($owner, 7);
        $this->assertSame(['submissions' => 0, 'approved' => 1, 'rejected' => 1], $report['totals']);
        $this->assertSame(1, $report['daily']['2026-10-27']['decisions']);
        $this->assertSame(0, $report['daily']['2026-10-26']['decisions']);
    }

    public function test_president_can_filter_modules_and_empty_series_have_valid_scales(): void
    {
        $this->enableHistory();
        $president = $this->actor('President');
        $owner = $this->actor();
        $this->record($owner, '2026-10-25 12:00:00');
        $document = $this->record($this->actor('Secretary'), '2026-10-25 12:00:00', [], Document::class);
        $this->event($document, 'approved', '2026-10-26 12:00:00');
        $all = app(ContentActivity::class)->forUser($president, 30);
        $this->assertSame(2, $all['totals']['submissions']);
        $this->assertCount(30, $all['daily']);
        $response = $this->actingAs($president)->get(route('workspace.content-activity.index', ['days' => 7, 'module' => 'documents']))->assertOk();
        $this->assertSame(['submissions' => 1, 'approved' => 1, 'rejected' => 0], $response->viewData('report')['totals']);
        $response->assertSee(route('workspace.review-history.index', ['module' => 'documents', 'from' => '2026-10-21', 'to' => '2026-10-27']));
        $empty = app(ContentActivity::class)->forUser($president, 7, 'activities');
        $this->assertSame(1, $empty['peak']);
        $this->assertSame(0, array_sum($empty['totals']));
    }

    public function test_role_and_filter_validation_prevent_scope_bypass(): void
    {
        $this->get(route('workspace.content-activity.index'))->assertRedirect();
        foreach (['student', 'adviser', 'superadmin'] as $role) {
            $this->actingAs($this->actor('President', $role))->get(route('workspace.content-activity.index'))->assertForbidden();
        }
        $owner = $this->actor();
        $this->actingAs($owner);
        foreach ([['days' => 365], ['days' => ['7']], ['module' => 'documents'], ['module' => ['articles']]] as $filters) {
            $this->getJson(route('workspace.content-activity.index', $filters))->assertUnprocessable();
        }
        $this->record($owner, '2026-10-25 12:00:00');
        $owner->officer_position = 'Secretary';
        $owner->save();
        $this->assertSame(0, app(ContentActivity::class)->forUser($owner, 7)['totals']['submissions']);
    }
}
