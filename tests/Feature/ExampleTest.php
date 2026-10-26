<?php

namespace Tests\Feature;

use App\Models\OfficerTerm;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee("What's New at SSITE", false)
            ->assertSee('No articles have been published yet.')
            ->assertSee('class="login-popover-divider"', false);
    }

    public function test_failed_login_returns_home_with_login_modal_open(): void
    {
        $guard = Auth::getFacadeRoot()->guard();

        Auth::shouldReceive('guard')->zeroOrMoreTimes()->andReturn($guard);
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'student@mcc.edu.ph',
                'password' => 'wrong-password',
            ], false)
            ->andReturn(false);

        $response = $this->from('/')->post('/login', [
            'email' => 'student@mcc.edu.ph',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_error_is_shown_in_the_home_login_popover(): void
    {
        $this->withSession(['error' => 'Sign in first before proceeding.'])
            ->get('/')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('Sign in first before proceeding.');
    }

    public function test_normal_login_accepts_non_mcc_email_addresses(): void
    {
        $guard = Auth::getFacadeRoot()->guard();
        $user = new User;
        $user->role = 'student';

        Auth::shouldReceive('guard')->andReturn($guard);
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'person@example.com',
                'password' => 'valid-password',
            ], false)
            ->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $this->post('/login', [
            'email' => 'person@example.com',
            'password' => 'valid-password',
        ])->assertRedirect(route('profile.complete'));
    }

    public function test_about_page_is_public_and_renders_the_officers(): void
    {
        OfficerTerm::create(['academic_year' => AcademicYear::current(), 'name' => 'Current officer fixture', 'position' => 'President', 'sort_order' => 0]);
        OfficerTerm::create(['academic_year' => '2024-2025', 'name' => 'Past officer fixture', 'position' => 'Secretary', 'sort_order' => 0]);
        $this->get('/about')->assertOk()
            ->assertSee('Mission')->assertSee('Vision')
            ->assertSee('Current officer fixture')->assertSee('Past officer fixture');
    }

    public function test_guest_is_redirected_home_with_login_modal_for_protected_pages(): void
    {
        $this->get('/home')
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Sign in first before proceeding.');

        $this->get('/')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('Sign in first before proceeding.');

        $this->get('/login')
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Sign in first before proceeding.');
    }

    public function test_officer_can_view_the_shared_role_dashboard_but_students_cannot(): void
    {
        $officer = User::factory()->make();
        $officer->role = 'officer';
        $officer->officer_position = 'Multimedia';
        $officer->user_id = 101;
        $officer->profile_completed_at = now();

        $this->actingAs($officer)
            ->get(route('officer.dashboard'))
            ->assertOk()
            ->assertSee('Recent submissions')
            ->assertSee('Dashboard')
            ->assertSee('Your content')
            ->assertSee('href="'.route('officer.dashboard').'"', false);

        $student = User::factory()->make();
        $student->role = 'student';
        $student->profile_completed_at = now();

        $this->actingAs($student)
            ->get(route('officer.dashboard'))
            ->assertForbidden();
    }

    public function test_students_cannot_access_adviser_role_management(): void
    {
        $student = User::factory()->make();
        $student->role = 'student';

        $this->actingAs($student)
            ->get(route('adviser.users.index'))
            ->assertForbidden();
    }

    public function test_adviser_cannot_review_content(): void
    {
        $adviser = User::factory()->make();
        $adviser->role = 'adviser';
        $adviser->profile_completed_at = now();

        $this->actingAs($adviser)
            ->get(route('adviser.reviews.index'))
            ->assertForbidden();
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = new User(['role' => 'adviser']);

        $this->assertNull($user->role);
    }

    public function test_incomplete_profile_blocks_feature_pages_but_allows_the_form(): void
    {
        $student = User::factory()->make();
        $student->role = 'student';

        $this->actingAs($student)
            ->get('/articles')
            ->assertRedirect(route('profile.complete'))
            ->assertSessionHas('url.intended', route('articles.index'));

        $this->get(route('profile.complete'))
            ->assertOk()
            ->assertSee('Please complete your information to continue')
            ->assertSee('Logout')
            ->assertDontSee('About us');
    }

    public function test_completed_profile_can_use_feature_pages_and_completion_url_redirects_home(): void
    {
        $student = User::factory()->make();
        $student->role = 'student';
        $student->profile_completed_at = now();

        $this->actingAs($student)
            ->get('/articles')
            ->assertOk();

        $this->get(route('profile.complete'))
            ->assertRedirect(route('home'));
    }

    public function test_incomplete_profile_ajax_request_receives_forbidden_json(): void
    {
        $student = User::factory()->make();
        $student->role = 'student';

        $this->actingAs($student)
            ->getJson('/articles')
            ->assertForbidden()
            ->assertJsonPath('profile_url', route('profile.complete'));
    }
}
