<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Latest Updates')
            ->assertSee('Featured Stories')
            ->assertSee('Most Read')
            ->assertSee('Latest Activities')
            ->assertSee('Featured Activities')
            ->assertSee('Most Viewed')
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
        $user = new User();
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
        $user = User::factory()->make();
        $user->profile_completed_at = now();

        $this->actingAs($user)->get('/about')
            ->assertOk()
            ->assertSee('Get to Know SSITE')
            ->assertSee('Mission')
            ->assertSee('Vision')
            ->assertSee('SSITE Officers A.Y. 2026-2027')
            ->assertSee('Khyle Alegre')
            ->assertSee('images/officerimg/Khyle Alegre 1.png')
            ->assertSee('Leadership History')
            ->assertSee('https://placehold.co/320x320', false)
            ->assertSee('css/about.css');
    }

    public function test_guest_is_redirected_home_with_login_modal_for_protected_pages(): void
    {
        $this->get('/about')
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
        $officer->profile_completed_at = now();

        $this->actingAs($officer)
            ->get(route('officer.dashboard'))
            ->assertOk()
            ->assertSee('My recent posts')
            ->assertSee('Dashboard')
            ->assertSee('New Post')
            ->assertSee('href="' . route('officer.dashboard') . '"', false);

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

    public function test_adviser_can_view_the_review_queue(): void
    {
        $adviser = User::factory()->make();
        $adviser->role = 'adviser';
        $adviser->profile_completed_at = now();

        $this->actingAs($adviser)
            ->get(route('adviser.reviews.index'))
            ->assertOk()
            ->assertSee('Review Posts')
            ->assertSee('Pending')
            ->assertSee('Reject post')
            ->assertSee('not implemented yet');
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
            ->assertSessionHas('url.intended', route('articles'));

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
