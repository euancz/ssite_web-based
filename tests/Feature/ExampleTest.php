<?php

namespace Tests\Feature;

use App\Models\OfficerTerm;
use App\Models\User;
use App\Support\AcademicYear;
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

    public function test_the_application_returns_the_public_microsoft_landing_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Campus life.')
            ->assertSee(route('microsoft.login'))->assertDontSee('name="password"', false);
    }

    public function test_password_login_is_not_an_available_authentication_method(): void
    {
        $this->post('/login', ['email' => 'student@mcc.edu.ph', 'password' => 'wrong-password'])->assertStatus(405);
        $this->assertGuest();
    }

    public function test_login_error_is_shown_accessibly_on_the_landing_page(): void
    {
        $this->withSession(['error' => 'Sign in first before proceeding.'])->get('/')
            ->assertSee('role="alert"', false)->assertSee('Sign in first before proceeding.');
    }

    public function test_arbitrary_email_and_password_cannot_create_an_authenticated_session(): void
    {
        $this->post('/login', ['email' => 'person@example.com', 'password' => 'valid-password'])->assertStatus(405);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_about_requires_sign_in_and_renders_current_and_past_officers_to_students(): void
    {
        OfficerTerm::create(['academic_year' => AcademicYear::current(), 'name' => 'Current officer fixture', 'position' => 'President', 'sort_order' => 0]);
        OfficerTerm::create(['academic_year' => '2024-2025', 'name' => 'Past officer fixture', 'position' => 'Secretary', 'sort_order' => 0]);
        $this->get('/about')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]))
            ->get('/about')->assertOk()->assertSee('Mission')->assertSee('Vision')
            ->assertSee('Current officer fixture')->assertSee('Past officer fixture');
    }

    public function test_guest_is_redirected_to_the_microsoft_landing_page(): void
    {
        $this->get('/home')->assertRedirect('/')->assertSessionHas('error', 'Sign in first before proceeding.');
        $this->get('/')->assertSee('role="alert"', false)->assertSee('Sign in first before proceeding.');
        $this->get('/login')->assertRedirect('/')->assertSessionHas('error', 'Sign in first before proceeding.');
    }

    public function test_officer_can_view_the_shared_role_dashboard_but_students_cannot(): void
    {
        $officer = User::factory()->make();
        $officer->role = 'officer';
        $officer->officer_position = 'Multimedia';
        $officer->id = 101;
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
