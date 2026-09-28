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

        Auth::shouldReceive('guard')->andReturn($guard);
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

        $this->get('/')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('The email or password is incorrect.');
    }

    public function test_normal_login_accepts_non_mcc_email_addresses(): void
    {
        $guard = Auth::getFacadeRoot()->guard();

        Auth::shouldReceive('guard')->andReturn($guard);
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'person@example.com',
                'password' => 'valid-password',
            ], false)
            ->andReturn(true);

        $this->post('/login', [
            'email' => 'person@example.com',
            'password' => 'valid-password',
        ])->assertRedirect('/');
    }

    public function test_about_page_is_public_and_renders_the_officers(): void
    {
        $this->actingAs(User::factory()->make())->get('/about')
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
}
