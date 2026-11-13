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
            ['officer', 'President'], ['officer', 'Multimedia'], ['officer', 'Secretary'],
            ['officer', 'Auditor'], ['adviser', null], ['superadmin', null],
        ] as [$role, $position]) {
            $user = $this->actor($role, $position);
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
        }
    }

    public function test_dashboard_counts_and_recent_content_remain_owner_scoped(): void
    {
        $owner = $this->actor('officer', 'Multimedia');
        $other = $this->actor('officer', 'Multimedia');
        foreach ([[$owner, 'My community story', 'approved'], [$other, 'Private other story', 'pending']] as [$author, $title, $status]) {
            DB::table('articles')->insert(['title' => $title, 'content' => 'Story text', 'created_by_user_id' => $author->id,
                'approval_status' => $status, 'content_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
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
        ]))->assertOk()->assertSee('aria-label="Submission status"', false)
            ->assertSee('value="title" selected', false)->assertSee('value="Minutes"', false)
            ->assertSee('value="2" selected', false)->assertSee('Create document');
        $response->assertDontSee('href="'.route('articles.create').'"', false);
        foreach ($response->viewData('allowedTabs') as $tab) {
            $response->assertSee('href="'.e(route('workspace.posts.documents.index', ['tab' => $tab])).'"', false);
        }
    }

    public function test_workspace_tools_do_not_load_on_student_pages(): void
    {
        $this->actingAs($this->actor('student', null))->get(route('articles.index'))->assertOk()
            ->assertDontSee('workspace-refresh.css')->assertDontSee('workspace-ui.js')
            ->assertDontSee('id="workspace-jump"', false);
    }
}
