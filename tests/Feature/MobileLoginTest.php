<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileLogin;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MobileLoginTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        config(['mobile.web_origins' => ['https://app.example.test']]);
    }

    private function student(): User
    {
        return User::forceCreate([
            'name' => 'App Student', 'email' => 'app@mcc.edu.ph',
            'role' => 'student', 'profile_completed_at' => now(),
        ]);
    }

    public function test_browser_sign_in_returns_a_one_time_code_that_can_authenticate_api_requests(): void
    {
        $response = $this->actingAs($this->student())->get('/auth/microsoft/mobile?' . http_build_query([
            'return_url' => 'https://app.example.test/ssite/',
        ]))->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://app.example.test/ssite/#ssite_code=', $location);
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $fragment);
        $this->assertSame(64, strlen($fragment['ssite_code']));
        $this->assertFalse(session()->has('mobile_login'));
        $this->assertFalse(session()->has('mobile_login_return_url'));
        $exchange = $this->postJson('/api/mobile/v1/auth/exchange', ['code' => $fragment['ssite_code']])
            ->assertOk()->assertJsonPath('user.email', 'app@mcc.edu.ph');
        $this->withToken($exchange->json('token'))->getJson('/api/mobile/v1/auth/me')->assertOk();
        $this->postJson('/api/mobile/v1/auth/exchange', ['code' => $fragment['ssite_code']])->assertUnauthorized();
    }

    public function test_microsoft_callback_returns_to_browser_after_identity_verification(): void
    {
        $user = $this->student();
        $user->forceFill(['microsoft_id' => 'verified-microsoft-id'])->save();
        $identity = (new \Laravel\Socialite\Two\User)->map([
            'id' => 'verified-microsoft-id', 'email' => $user->email, 'name' => $user->name,
        ]);
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn(
            \Mockery::mock()->shouldReceive('user')->once()->andReturn($identity)->getMock()
        );
        $response = $this->withSession([
            'mobile_login' => true, 'mobile_login_return_url' => 'https://app.example.test/',
        ])->get('/auth/microsoft/callback')->assertRedirect();
        $this->assertStringStartsWith('https://app.example.test/#ssite_code=', $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_native_sign_in_keeps_the_app_deep_link(): void
    {
        $response = $this->actingAs($this->student())->get('/auth/microsoft/mobile')->assertRedirect();
        $this->assertStringStartsWith('ssiteapp://auth/callback?code=', $response->headers->get('Location'));
    }

    public function test_untrusted_return_addresses_are_rejected_before_login(): void
    {
        foreach (['https://attacker.test/', 'https://app.example.test.attacker.test/',
            'https://app.example.test@attacker.test/', 'javascript:alert(1)',
            'https://app.example.test/?next=attacker', 'http://localhost:54321/', ['bad']] as $url) {
            $this->get('/auth/microsoft/mobile?' . http_build_query(['return_url' => $url]))->assertStatus(422);
        }
        $this->assertGuest();
        $this->assertFalse(session()->has('mobile_login'));
    }

    public function test_local_flutter_ports_are_allowed_only_in_local_environment(): void
    {
        $this->assertFalse(MobileLogin::allowsWebReturnUrl('http://localhost:54321/'));
        $this->app->instance('env', 'local');
        $this->assertTrue(MobileLogin::allowsWebReturnUrl('http://localhost:54321/'));
        $this->assertFalse(MobileLogin::allowsWebReturnUrl('http://localhost.attacker.test:54321/'));
    }

    public function test_incomplete_profile_keeps_browser_destination_until_profile_completion(): void
    {
        $user = $this->student();
        $user->forceFill(['profile_completed_at' => null])->save();
        $this->actingAs($user)->get('/auth/microsoft/mobile?' . http_build_query([
            'return_url' => 'https://app.example.test/',
        ]))->assertRedirect(route('profile.complete'))
            ->assertSessionHas('mobile_login', true)
            ->assertSessionHas('mobile_login_return_url', 'https://app.example.test/');
    }
}
