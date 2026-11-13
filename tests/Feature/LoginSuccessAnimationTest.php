<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as MicrosoftUser;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class LoginSuccessAnimationTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->withoutVite();
    }

    public function test_successful_login_renders_animation_once_on_the_destination_page(): void
    {
        config(['school.microsoft_email_domain' => 'mcc.edu.ph']);
        $identity = (new MicrosoftUser)->map([
            'id' => 'animation-test-id', 'email' => 'animation@mcc.edu.ph', 'name' => 'CMS Member',
        ]);
        Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn(
            \Mockery::mock()->shouldReceive('user')->once()->andReturn($identity)->getMock()
        );

        $this->get(route('microsoft.callback'))
            ->assertRedirect(route('profile.complete'))
            ->assertSessionHas('show_login_success', true);

        $this->get(route('profile.complete'))->assertOk()
            ->assertSee('id="login-success"', false)
            ->assertSee('login-success.css')
            ->assertSee('login-success.js')
            ->assertSessionMissing('show_login_success');

        $this->get(route('profile.complete'))->assertOk()
            ->assertDontSee('id="login-success"', false)
            ->assertDontSee('login-success.js');
    }

    public function test_normal_cms_page_renders_without_the_animation(): void
    {
        $user = User::forceCreate([
            'name' => 'CMS Member', 'email' => 'normal@mcc.edu.ph', 'role' => 'student',
            'profile_completed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertDontSee('id="login-success"', false)
            ->assertDontSee('login-success.css');
    }
}
