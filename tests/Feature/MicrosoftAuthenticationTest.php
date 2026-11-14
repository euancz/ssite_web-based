<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as MicrosoftUser;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MicrosoftAuthenticationTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        config(['school.microsoft_email_domain' => 'mcc.edu.ph']);
    }

    public function test_web_login_switches_to_callback_host_before_starting_oauth(): void
    {
        config(['services.microsoft.redirect' => 'http://localhost:8000/auth/microsoft/callback']);
        Socialite::shouldReceive('driver')->never();

        $this->get('http://127.0.0.1:8000/auth/microsoft')
            ->assertRedirect('http://localhost:8000/auth/microsoft')
            ->assertSessionMissing('state');
    }

    public function test_flutter_web_login_preserves_return_url_when_switching_hosts(): void
    {
        config([
            'services.microsoft.redirect' => 'http://localhost:8000/auth/microsoft/callback',
            'mobile.web_origins' => ['http://127.0.0.1:54321'],
        ]);
        Socialite::shouldReceive('driver')->never();
        $query = http_build_query(['return_url' => 'http://127.0.0.1:54321/']);

        $this->get('http://127.0.0.1:8000/auth/microsoft/mobile?' . $query)
            ->assertRedirect('http://localhost:8000/auth/microsoft/mobile?' . $query)
            ->assertSessionMissing('state')
            ->assertSessionMissing('mobile_login');
    }

    public function test_matching_callback_host_starts_oauth_without_a_redirect_loop(): void
    {
        config(['services.microsoft.redirect' => 'http://localhost:8000/auth/microsoft/callback']);
        $provider = \Mockery::mock();
        Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($provider);
        $provider->shouldReceive('with')->with(['prompt' => 'login'])->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://login.microsoftonline.com/'));

        $this->get('http://localhost:8000/auth/microsoft')
            ->assertRedirect('https://login.microsoftonline.com/');
    }

    private function microsoft(string $email): void
    {
        $identity = (new MicrosoftUser)->map(['id' => 'verified-microsoft-id', 'email' => $email, 'name' => 'Microsoft member']);
        Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn(
            \Mockery::mock()->shouldReceive('user')->once()->andReturn($identity)->getMock()
        );
    }

    public function test_non_school_microsoft_account_is_rejected_without_creating_a_user(): void
    {
        $this->microsoft('member@example.com');
        $this->get('/auth/microsoft/callback')->assertRedirect('/')->assertSessionHas('error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_new_school_account_is_a_student_and_must_complete_its_profile(): void
    {
        $this->microsoft('member@mcc.edu.ph');
        $this->get('/auth/microsoft/callback')->assertRedirect(route('profile.complete'));
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('student', $user->role);
        $this->assertNull($user->profile_completed_at);
        $this->get('/articles')->assertRedirect(route('profile.complete'));
    }

    public function test_existing_linked_account_keeps_its_role_and_profile(): void
    {
        $user = new User;
        $user->forceFill(['name' => 'Locally edited name', 'email' => 'member@mcc.edu.ph', 'microsoft_id' => 'verified-microsoft-id', 'role' => 'officer', 'officer_position' => 'Secretary', 'profile_completed_at' => now()])->save();
        $this->microsoft('member@mcc.edu.ph');
        $this->get('/auth/microsoft/callback')->assertRedirect(route('home'));
        $this->assertSame('Locally edited name', $user->fresh()->name);
        $this->assertSame('Secretary', $user->fresh()->officer_position);
        $this->assertDatabaseCount('users', 1);
    }
}
