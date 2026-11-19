<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MicrosoftAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');
        $this->microsoft('member@mcc.edu.ph');
        $this->get('/auth/microsoft/callback')->assertRedirect(route('profile.complete'));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->get('/articles')->assertRedirect(route('profile.complete'));
        $this->get(route('profile.complete'))->assertOk()
            ->assertSee(route('policies').'#terms-of-service')
            ->assertSee(route('policies').'#data-privacy')
            ->assertSee('Decline and sign out');
        $this->get(route('policies'))->assertOk()->assertSee('Terms of service')->assertSee('Privacy policy');

        $this->from(route('profile.complete'))->post(route('profile.complete.store'), [
            'picture' => $this->picture(),
        ])->assertSessionHasErrors('accept_policies');
        $this->post(route('profile.complete.store'), [
            'accept_policies' => '1',
        ])->assertSessionHasErrors('picture');
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('profile-pictures'));

        $this->post(route('profile.complete.store'), [
            'picture' => $this->picture(), 'accept_policies' => '1', 'role' => 'admin', 'student_number' => 'ignored',
        ])->assertRedirect(route('articles.index'))->assertSessionMissing(MicrosoftAccount::PENDING_SESSION_KEY);
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertNotNull($user->profile_completed_at);
        $this->assertNotNull($user->profile_policies_accepted_at);
        Storage::disk('public')->assertExists($user->profile_picture);
    }

    public function test_declining_discards_pending_sign_in_and_creates_no_account_or_photo(): void
    {
        Storage::fake('public');
        $this->microsoft('member@mcc.edu.ph');
        $this->withSession(['mobile_login' => true])->get(route('microsoft.callback'))
            ->assertRedirect(route('profile.complete'));

        $this->post(route('logout'))->assertRedirect('/')
            ->assertSessionMissing(MicrosoftAccount::PENDING_SESSION_KEY)
            ->assertSessionMissing('mobile_login');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('profile-pictures'));
        $this->post(route('profile.complete.store'), [
            'picture' => $this->picture(), 'accept_policies' => '1',
        ])->assertRedirect('/');
    }

    public function test_profile_settings_only_edit_the_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'profile_completed_at' => now(),
            'profile_policies_accepted_at' => now()->subDay(),
        ]);
        $page = $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('Edit profile picture')
            ->assertSee('name="picture" accept=".jpg,.jpeg,.png"', false)
            ->assertDontSee('Decline and sign out')
            ->assertDontSee('Remove photo');
        foreach (['accept_policies', 'gender', 'student_number', 'institute', 'program', 'year_level', 'contact_number', 'address'] as $field) {
            $page->assertDontSee('name="'.$field.'"', false);
        }
        $this->put('/profile', ['gender' => 'ignored'])->assertNotFound();
        $this->from(route('profile.edit'))->post(route('profile.picture.update'), [
            'picture' => $this->webp('photo.webp'),
        ])->assertSessionHasErrors(['picture' => 'Use a JPG or PNG image.']);
        $this->post(route('profile.picture.update'), [
            'picture' => $this->webp('photo.png'),
        ])->assertSessionHasErrors(['picture' => 'Use a JPG or PNG image.']);
        $this->assertNull($user->fresh()->profile_picture);

        $this->post(route('profile.picture.update'), [
            'picture' => $this->picture(),
        ])->assertRedirect(route('profile.edit'));
        $updated = $user->fresh();
        $this->assertEquals($user->profile_policies_accepted_at, $updated->profile_policies_accepted_at);
        Storage::disk('public')->assertExists($updated->profile_picture);

        $this->actingAs($updated)->get(route('profile.edit'))->assertOk()->assertSee('Remove photo');
        $this->delete(route('profile.picture.remove'))->assertRedirect(route('profile.edit'));
        $this->assertNull($user->fresh()->profile_picture);
        Storage::disk('public')->assertMissing($updated->profile_picture);
    }

    public function test_first_mobile_sign_in_returns_a_code_only_after_photo_and_consent(): void
    {
        Storage::fake('public');
        $this->microsoft('member@mcc.edu.ph');
        $this->withSession(['mobile_login' => true])->get(route('microsoft.callback'))
            ->assertRedirect(route('profile.complete'));
        $this->assertGuest();

        $response = $this->post(route('profile.complete.store'), [
            'picture' => $this->picture(), 'accept_policies' => '1',
        ])->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('ssiteapp://auth/callback?code=', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $this->postJson(route('api.mobile.auth.exchange'), ['code' => $params['code']])
            ->assertOk()->assertJsonPath('user.email', 'member@mcc.edu.ph');
    }

    private function picture(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    private function webp(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'
        ))->mimeType('image/webp');
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
