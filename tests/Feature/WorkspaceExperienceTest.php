<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class WorkspaceExperienceTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function actor(string $role, ?string $position): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Alex Rivera', 'email' => uniqid().'@mcc.edu.ph', 'role' => $role,
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    public function test_dashboard_shortcuts_and_quick_navigation_respect_every_workspace_role(): void
    {
        foreach ([
            ['officer', 'President'], ['officer', 'Social Media Manager'], ['officer', 'Secretary'],
            ['officer', 'Auditor'], ['adviser', null], ['admin', null],
        ] as [$role, $position]) {
            $user = $this->actor($role, $position);
            $this->actingAs($user)->get(route($user->dashboardRoute()), ['Sec-Fetch-Dest' => 'document'])->assertOk()
                ->assertSee('workspace-welcome')->assertSee('id="workspace-sidebar"', false)
                ->assertSee('aria-busy="true"', false);
            $this->get(route($user->dashboardRoute()), ['Sec-Fetch-Dest' => 'empty', 'X-SSITE-Page-Data' => '1'])->assertOk()
                ->assertSee('data-page-data', false)->assertDontSee('id="workspace-sidebar"', false)
                ->assertDontSee('aria-busy="true"', false);
            $response = $this->actingAs($user)->get(route($user->dashboardRoute()))->assertOk()
                ->assertSee('workspace-refresh.css')->assertSee('id="workspace-jump"', false)
                ->assertSee('id="workspace-preferences"', false)->assertSee('workspace-welcome');
            foreach (config('workspace.modules') as $key => $module) {
                $link = 'href="'.route($module['create']).'"';
                if ($user->canPostIn($key)) {
                    $response->assertSee($link, false);
                } else {
                    $response->assertDontSee($link, false);
                }
            }
            $reviewLink = 'href="'.route('adviser.reviews.index', ['status' => 'pending']).'"';
            if ($user->canReview()) {
                $response->assertSee($reviewLink, false);
            } else {
                $response->assertDontSee($reviewLink, false);
            }

            $response->assertSee('workspace-dashboard-summary');
            if ($user->isAdviser()) {
                $response->assertDontSee('workspace-welcome-actions')
                    ->assertSee('href="'.route('adviser.users.index', ['role' => 'officer']).'"', false)
                    ->assertSee('href="'.route('adviser.users.index', ['role' => 'student']).'"', false)
                    ->assertDontSee('Manage adviser access');
            } elseif ($user->isAdmin()) {
                $response->assertSee('Manage adviser access')
                    ->assertDontSee('workspace-welcome-actions')
                    ->assertDontSee('Recent role changes')
                    ->assertDontSee('Manage accounts')
                    ->assertDontSee('href="'.route('adviser.users.index', ['role' => 'officer']).'"', false);
            }
        }
    }

    public function test_dashboard_counts_and_recent_content_remain_owner_scoped(): void
    {
        $owner = $this->actor('officer', 'Social Media Manager');
        $other = $this->actor('officer', 'Social Media Manager');
        foreach ([[$owner, 'My community story', 'approved'], [$other, 'Private other story', 'pending']] as [$author, $title, $status]) {
            DB::table('articles')->insert(['title' => $title, 'content' => 'Story text', 'created_by_user_id' => $author->id,
                'approval_status' => $status, 'content_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        $feed = route('workspace.posts.articles.index', ['tab' => 'mine']);
        $this->actingAs($owner)->get($feed, ['Sec-Fetch-Dest' => 'document'])->assertOk()
            ->assertSee('My submissions')->assertSee('data-feed-initial', false)->assertDontSee('My community story');
        $this->get($feed, ['Sec-Fetch-Dest' => 'empty'])->assertOk()
            ->assertSee('My community story')->assertDontSee('Private other story')->assertDontSee('data-feed-initial>', false);
        $response = $this->actingAs($owner)->get(route('officer.dashboard'))->assertOk()
            ->assertSee('My community story')->assertDontSee('Private other story')->assertSee('Your content areas');
        $this->assertSame(1, $response->viewData('dashboardStatus')['published']);
        $this->assertSame(0, $response->viewData('dashboardStatus')['pending']);
    }

    public function test_library_offers_authorized_status_tabs_and_preserves_sort_and_search(): void
    {
        $user = $this->actor('officer', 'Secretary');
        $response = $this->actingAs($user)->get(route('workspace.posts.documents.index', [
            'tab' => 'mine', 'sort' => 'title', 'q' => 'Minutes', 'semester' => '2',
        ]))->assertOk()->assertSee('aria-label="My work pages"', false)
            ->assertSee('value="title" selected', false)->assertSee('value="Minutes"', false)
            ->assertSee('value="2" selected', false)->assertSee('Create document');
        $response->assertDontSee('href="'.route('articles.create').'"', false);
        $response->assertSee('href="'.e(route('workspace.drafts.index', ['module' => 'documents'])).'"', false)
            ->assertSee('href="'.e(route('workspace.posts.documents.index', ['tab' => 'mine'])).'"', false)
            ->assertSee('aria-label="Manage posts"', false)
            ->assertSee('aria-label="Activity log"', false);
    }

    public function test_workspace_tools_do_not_load_on_student_pages(): void
    {
        $this->actingAs($this->actor('student', null))->get(route('articles.index'))->assertOk()
            ->assertDontSee('workspace-refresh.css')->assertDontSee('workspace-ui.js')
            ->assertDontSee('id="workspace-jump"', false);
    }
}
