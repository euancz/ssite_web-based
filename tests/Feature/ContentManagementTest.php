<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ContentModules;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function actor(string $position = 'President', string $name = 'Test officer'): User
    {
        $user = new User;
        $user->forceFill(['name' => $name, 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function makePost(string $module, User $author, array $attributes = []): int
    {
        $model = new (ContentModules::all()[$module]['model']);
        $data = ['title' => $module.' submission', 'created_by_user_id' => $author->user_id,
            'approval_status' => 'pending', 'content_status' => 'active',
            'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-01 12:00:00',
            $module === 'articles' ? 'content' : 'description' => 'Submission text'];
        if (in_array($module, ['documents', 'liquidations'], true)) {
            $data += ['file_path' => 'private.pdf', 'original_name' => 'Record.pdf', 'file_size' => 100];
        }

        return DB::table($module)->insertGetId(array_merge($data, $attributes), $model->getKeyName());
    }

    public function test_dashboard_counts_and_recent_records_follow_module_ownership_and_public_visibility(): void
    {
        $owner = $this->actor('Multimedia');
        $other = $this->actor('Multimedia');
        $this->makePost('articles', $owner, ['title' => 'My published article', 'approval_status' => 'approved']);
        $this->makePost('articles', $owner, ['approval_status' => 'approved', 'content_status' => 'archived']);
        $this->makePost('articles', $owner, ['title' => 'Fix this article', 'approval_status' => 'rejected', 'reviewed_at' => '2026-10-20 10:30:00', 'reviewed_by_user_id' => $other->user_id, 'rejection_reason' => 'Add the venue']);
        $this->makePost('articles', $other, ['title' => 'Private foreign title']);
        $this->makePost('documents', $owner, ['title' => 'Former secretary submission']);
        $response = $this->actingAs($owner)->get(route('officer.dashboard'))->assertOk()
            ->assertSee('My published article')->assertSee('Add the venue')->assertSee('Oct 20, 2026')
            ->assertDontSee('Private foreign title')->assertDontSee('Former secretary submission');
        $stats = $response->viewData('moduleStats');
        $this->assertSame(['articles', 'activities', 'achievements'], array_keys($stats));
        $this->assertSame(3, $stats['articles']['total']);
        $this->assertSame(1, $stats['articles']['published']);
        $this->assertSame(1, $stats['articles']['archived']);
        $this->assertSame(1, $stats['articles']['rejected']);
        $this->assertSame(0, $stats['articles']['pending']);
        $this->assertSame(0, $stats['activities']['total']);
    }

    public function test_president_dashboard_prioritizes_oldest_pending_records_across_modules(): void
    {
        $president = $this->actor();
        foreach (array_keys(ContentModules::all()) as $module) {
            $this->makePost($module, $president);
        }
        $this->makePost('documents', $president, ['title' => 'Oldest document', 'created_at' => '2026-09-01 10:00:00']);
        $this->makePost('articles', $president, ['approval_status' => 'approved', 'content_status' => 'archived']);
        $response = $this->actingAs($president)->get(route('officer.dashboard'))->assertOk()->assertSee('Oldest document');
        $this->assertSame('Oldest document', $response->viewData('pendingPosts')->first()->title);
        $this->assertCount(5, $response->viewData('pendingPosts'));
        $this->assertSame(['pending' => 6, 'published' => 0, 'rejected' => 0, 'archived' => 1], $response->viewData('dashboardStatus'));
    }

    public function test_review_queue_renders_pending_actions_for_every_module_and_keeps_current_decision_details(): void
    {
        $president = $this->actor('President', 'Current reviewer');
        foreach (array_keys(ContentModules::all()) as $module) {
            $this->makePost($module, $president);
        }
        $response = $this->actingAs($president)->get(route('adviser.reviews.index'))->assertOk();
        $this->assertCount(5, $response->viewData('posts'));
        foreach (ContentModules::all() as $key => $module) {
            $response->assertSee(route($key.'.approve', 1), false)->assertSee('Reject '.strtolower($module['singular']));
        }
        $this->makePost('articles', $president, ['title' => 'Reviewed article', 'approval_status' => 'rejected',
            'reviewed_at' => '2026-10-20 14:30:00', 'reviewed_by_user_id' => $president->user_id,
            'rejection_reason' => '<script>alert("unsafe")</script>']);
        $this->get(route('adviser.reviews.index', ['status' => 'rejected']))->assertOk()
            ->assertSee('Current reviewer')->assertSee('Oct 20, 2026 2:30 PM')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_review_filters_combine_and_status_tabs_preserve_filters(): void
    {
        $president = $this->actor();
        $author = $this->actor('Secretary', 'Specific author');
        $this->makePost('documents', $author, ['title' => 'End of day document', 'created_at' => '2026-10-02 23:59:59']);
        $this->makePost('documents', $author, ['title' => 'Matching approved', 'approval_status' => 'approved', 'created_at' => '2026-10-02 13:00:00']);
        $this->makePost('documents', $author, ['title' => 'Next day excluded', 'created_at' => '2026-10-03 00:00:00']);
        $this->makePost('articles', $author, ['title' => 'Other module excluded', 'created_at' => '2026-10-02 12:00:00']);
        $this->makePost('documents', $author, ['title' => 'Archived excluded', 'content_status' => 'archived', 'created_at' => '2026-10-02 12:00:00']);
        $filters = ['module' => 'documents', 'q' => 'Specific author', 'from' => '2026-10-02', 'to' => '2026-10-02', 'visibility' => 'active'];
        $response = $this->actingAs($president)->get(route('adviser.reviews.index', $filters))->assertOk()
            ->assertSee('End of day document')->assertDontSee('Next day excluded')->assertDontSee('Other module excluded')->assertDontSee('Archived excluded');
        $this->assertSame(1, (int) $response->viewData('statusCounts')['pending']);
        $this->assertSame(1, (int) $response->viewData('statusCounts')['approved']);
        $this->assertSame(1, $response->viewData('posts')->total());
        $response->assertSee(route('adviser.reviews.index', array_merge($filters, ['sort' => 'oldest', 'status' => 'approved'])));
        $this->get(route('adviser.reviews.index', array_merge($filters, ['status' => 'approved'])))->assertOk()->assertSee('Matching approved');
    }

    public function test_review_pagination_is_stable_with_identical_timestamps_and_retains_search(): void
    {
        $president = $this->actor();
        for ($i = 0; $i < 12; $i++) {
            $this->makePost('articles', $president, ['title' => 'Matching row '.$i]);
        }
        $filters = ['q' => 'Matching', 'module' => 'articles', 'sort' => 'oldest'];
        $first = $this->actingAs($president)->get(route('adviser.reviews.index', $filters))->assertOk()->viewData('posts');
        $second = $this->get(route('adviser.reviews.index', $filters + ['page' => 2]))->assertOk()->viewData('posts');
        $this->assertSame(12, $first->total());
        $this->assertCount(10, $first);
        $this->assertCount(2, $second);
        $this->assertEmpty($first->pluck('post_id')->intersect($second->pluck('post_id')));
        $this->assertStringContainsString('q=Matching', $first->nextPageUrl());
    }

    public function test_invalid_review_filters_and_unauthorized_review_requests_are_rejected(): void
    {
        $this->actingAs($this->actor());
        foreach ([['module' => 'users'], ['q' => ['array']], ['sort' => 'title;drop'], ['visibility' => 'hidden'],
            ['from' => '2026-10-03', 'to' => '2026-10-02'], ['from' => 'not-a-date']] as $filters) {
            $this->getJson(route('adviser.reviews.index', $filters))->assertUnprocessable();
        }
        $this->actingAs($this->actor('Secretary'))->get(route('adviser.reviews.index'))->assertForbidden();
    }

    public function test_workspace_search_cannot_escape_owner_scope_and_retains_pagination_filters(): void
    {
        $owner = $this->actor('Multimedia');
        $other = $this->actor('Multimedia');
        foreach (['articles', 'activities', 'achievements'] as $module) {
            for ($i = 0; $i < 6; $i++) {
                $this->makePost($module, $owner, ['title' => 'Find this '.$i]);
            }
            $this->makePost($module, $owner, ['title' => 'Unrelated record']);
            $this->makePost($module, $other, ['title' => 'Find this foreign private record']);
            $response = $this->actingAs($owner)->get(route('workspace.posts.'.$module.'.index', ['q' => 'Find this']))->assertOk()
                ->assertSee('Find this 0')->assertDontSee('Unrelated record')->assertDontSee('Find this foreign private record');
            $this->assertSame(6, $response->viewData($module)->total());
            $this->assertStringContainsString('q=Find%20this', $response->viewData($module)->nextPageUrl());
        }
    }

    public function test_reviewer_details_stay_in_workspace_and_forms_offer_safe_unsaved_previews(): void
    {
        $president = $this->actor('President', 'Reviewer identity');
        foreach (array_keys(ContentModules::all()) as $module) {
            $id = $this->makePost($module, $president, ['approval_status' => 'approved', 'reviewed_by_user_id' => $president->user_id, 'reviewed_at' => now()]);
            $this->actingAs($president)->get(route('workspace.posts.'.$module.'.show', $id))->assertOk()->assertSee('Latest decision');
            $this->get(route($module.'.show', $id))->assertOk()->assertDontSee('Latest decision');
            $this->get(route($module.'.create'))->assertOk()->assertSee('data-content-preview', false)->assertSee('Preview unsaved content');
        }
    }

    public function test_document_search_and_semester_filter_remain_owner_scoped_and_survive_sorting(): void
    {
        $owner = $this->actor('Secretary');
        $other = $this->actor('Secretary');
        $this->makePost('documents', $owner, ['title' => 'Match semester two', 'semester' => 2, 'category' => 'Minutes']);
        $this->makePost('documents', $owner, ['title' => 'Match semester one', 'semester' => 1, 'category' => 'Minutes']);
        $this->makePost('documents', $other, ['title' => 'Match private document', 'semester' => 2, 'category' => 'Minutes']);
        $filters = ['tab' => 'mine', 'q' => 'Match', 'semester' => '2', 'category' => 'Minutes'];
        $response = $this->actingAs($owner)->get(route('workspace.posts.documents.index', $filters))->assertOk()
            ->assertSee('Match semester two')->assertDontSee('Match semester one')->assertDontSee('Match private document');
        $this->assertSame(1, $response->viewData('documents')->total());
        $response->assertSee(route('workspace.posts.documents.index', ['tab' => 'mine', 'sort' => 'title', 'q' => 'Match', 'semester' => '2', 'category' => 'Minutes']));
    }
}
