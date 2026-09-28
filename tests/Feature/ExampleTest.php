<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
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

    public function test_about_page_is_public_and_renders_the_officers(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('Get to Know SSITE')
            ->assertSee('Mission')
            ->assertSee('Vision')
            ->assertSee('SSITE Officers A.Y. 2026-2027')
            ->assertSee('Kyle Alegre')
            ->assertSee('Leadership History')
            ->assertSee('https://placehold.co/320x320', false);
    }
}
