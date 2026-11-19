<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MobileWorkspaceTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function account(string $role = 'officer', ?string $position = 'Secretary'): User
    {
        return User::forceCreate([
            'name' => 'Mobile workspace user', 'email' => uniqid() . '@example.test',
            'role' => $role, 'officer_position' => $position, 'profile_completed_at' => now(),
        ]);
    }

    private function token(User $user): string
    {
        $id = 'workspace-' . $user->getKey();
        Cache::put('mobile_access:' . $id, $user->getKey(), now()->addHour());
        return Crypt::encryptString(json_encode([
            'sub' => $user->getKey(), 'jti' => $id, 'exp' => now()->addHour()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    public function test_workspace_access_follows_cms_capabilities_for_every_role(): void
    {
        foreach ([
            ['student', null, false], ['officer', null, false],
            ['officer', 'President', true], ['officer', 'Secretary', true],
            ['officer', 'Auditor', true], ['officer', 'Social Media Manager', true],
            ['adviser', null, true], ['admin', null, true], ['unknown', null, false],
        ] as [$role, $position, $allowed]) {
            $user = $this->account($role, $position);
            $this->withToken($this->token($user))->getJson('/api/mobile/v1/auth/me')
                ->assertOk()->assertJsonPath('user.can_access_workspace', $allowed);
            $response = $this->postJson('/api/mobile/v1/workspace/session');
            $allowed ? $response->assertOk()->assertJsonStructure(['url']) : $response->assertForbidden();
        }
    }

    public function test_workspace_session_requires_mobile_authentication(): void
    {
        $this->postJson('/api/mobile/v1/workspace/session')->assertUnauthorized();
    }

    public function test_handoff_authenticates_the_same_account_and_is_single_use(): void
    {
        $user = $this->account();
        $url = $this->withToken($this->token($user))->postJson('/api/mobile/v1/workspace/session')
            ->assertOk()->json('url');
        $this->get($url)->assertRedirect(route('officer.dashboard'))
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertAuthenticatedAs($user);
        $this->get($url)->assertUnauthorized();
    }

    public function test_expired_or_revoked_handoffs_are_rejected(): void
    {
        $user = $this->account();
        $url = $this->withToken($this->token($user))->postJson('/api/mobile/v1/workspace/session')->json('url');
        $this->travel(61)->seconds();
        $this->get($url)->assertUnauthorized();
        $this->travelBack();
        $url = $this->postJson('/api/mobile/v1/workspace/session')->json('url');
        Cache::forget('mobile_access:workspace-' . $user->getKey());
        $this->get($url)->assertUnauthorized();
    }

    public function test_role_is_checked_again_when_the_workspace_opens(): void
    {
        $user = $this->account();
        $url = $this->withToken($this->token($user))->postJson('/api/mobile/v1/workspace/session')->json('url');
        $user->forceFill(['role' => 'student', 'officer_position' => null])->save();
        $this->get($url)->assertForbidden();
        $this->postJson('/api/mobile/v1/workspace/session')->assertForbidden();
    }

    public function test_mobile_web_navigation_contains_all_five_main_destinations(): void
    {
        $this->actingAs($this->account('student', null))->get('/home')->assertOk()
            ->assertSee('class="mobile-tab-navigation"', false)
            ->assertSee('Home')->assertSee('Discover')->assertSee('Transparency')
            ->assertSee('Documents')->assertSee('About us')
            ->assertDontSee('aria-label="Open menu"', false);
    }
}