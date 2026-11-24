<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        (require database_path('migrations/2026_11_14_000002_create_sponsors_table.php'))->up();
        Storage::fake('public');
    }

    private function actor(string $role = 'officer', string $position = 'President'): User
    {
        return User::forceCreate(['name' => 'Sponsor Manager', 'email' => uniqid().'@mcc.edu.ph',
            'role' => $role, 'officer_position' => $position, 'profile_completed_at' => now()]);
    }

    private function data(array $values = []): array
    {
        return array_merge(['name' => 'Community Partner', 'description' => 'Supporting student opportunities.',
            'website_url' => 'https://example.com', 'sort_order' => 0, 'is_active' => 1], $values);
    }

    private function logo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('partner.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9uoAAAAASUVORK5CYII='
        ));
    }

    public function test_only_president_can_manage_sponsors(): void
    {
        $sponsor = Sponsor::create($this->data());
        $this->get(route('workspace.sponsors.index'))->assertRedirect();
        foreach ([['student', 'President'], ['officer', 'Secretary'], ['adviser', ''], ['admin', '']] as [$role, $position]) {
            $this->actingAs($this->actor($role, $position));
            $this->get(route('workspace.sponsors.index'))->assertForbidden();
            $this->get(route('workspace.sponsors.create'))->assertForbidden();
            $this->get(route('workspace.sponsors.edit', $sponsor))->assertForbidden();
            $this->post(route('workspace.sponsors.store'), $this->data())->assertForbidden();
            $this->put(route('workspace.sponsors.update', $sponsor), $this->data())->assertForbidden();
            $this->delete(route('workspace.sponsors.destroy', $sponsor))->assertForbidden();
        }
        $this->actingAs($this->actor())->get(route('workspace.sponsors.index'))->assertOk()->assertSee('Add sponsor');
        $this->get(route('workspace.sponsors.create'))->assertOk()
            ->assertSee('Attach a sponsor logo')->assertSee('accept=".jpg,.jpeg,.png"', false);
        $this->get(route('workspace.sponsors.edit', $sponsor))->assertOk();
    }

    public function test_create_replace_remove_and_delete_logo(): void
    {
        $this->actingAs($this->actor());
        $this->post(route('workspace.sponsors.store'), $this->data([
            'logo' => $this->logo(),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('workspace.sponsors.index'));
        $sponsor = Sponsor::firstOrFail();
        $firstLogo = $sponsor->logo_path;
        Storage::disk('public')->assertExists($firstLogo);
        $this->get(route('workspace.sponsors.edit', $sponsor))->assertOk()
            ->assertSee('Replace logo (optional)')->assertSee('id="sponsor-logo-preview"', false);
        $this->put(route('workspace.sponsors.update', $sponsor), $this->data([
            'logo' => $this->logo(), 'is_active' => 0,
        ]))->assertSessionHasNoErrors()->assertRedirect();
        Storage::disk('public')->assertMissing($firstLogo);
        $secondLogo = $sponsor->fresh()->logo_path;
        Storage::disk('public')->assertExists($secondLogo);
        $this->assertFalse($sponsor->fresh()->is_active);
        $this->put(route('workspace.sponsors.update', $sponsor), $this->data(['remove_logo' => 1]))->assertRedirect();
        Storage::disk('public')->assertMissing($secondLogo);
        $this->assertNull($sponsor->fresh()->logo_path);
        $this->put(route('workspace.sponsors.update', $sponsor), $this->data([
            'logo' => $this->logo(),
        ]))->assertRedirect();
        $lastLogo = $sponsor->fresh()->logo_path;
        $this->delete(route('workspace.sponsors.destroy', $sponsor))->assertRedirect();
        Storage::disk('public')->assertMissing($lastLogo);
        $this->assertDatabaseCount('sponsors', 0);
    }

    public function test_validation_rejects_unsafe_links_and_invalid_files(): void
    {
        $this->actingAs($this->actor());
        foreach (['javascript:alert(1)', 'data:text/html,test', 'ftp://example.com'] as $url) {
            $this->post(route('workspace.sponsors.store'), $this->data(['website_url' => $url]))
                ->assertSessionHasErrors('website_url');
        }
        $this->post(route('workspace.sponsors.store'), $this->data([
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            'sort_order' => -1,
        ]))->assertSessionHasErrors(['logo', 'sort_order']);
        $this->assertDatabaseCount('sponsors', 0);
    }

    public function test_webp_logos_are_rejected_by_extension_and_content(): void
    {
        $this->actingAs($this->actor());
        $png = $this->logo();
        $this->post(route('workspace.sponsors.store'), $this->data([
            'logo' => new UploadedFile($png->path(), 'partner.webp', test: true),
        ]))->assertSessionHasErrors('logo');
        $this->assertDatabaseCount('sponsors', 0);

        $webp = UploadedFile::fake()->createWithContent('partner.webp', base64_decode(
            'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'
        ));
        $sponsor = Sponsor::create($this->data());
        $this->put(route('workspace.sponsors.update', $sponsor), $this->data([
            'logo' => new UploadedFile($webp->path(), 'partner.png', test: true),
        ]))->assertSessionHasErrors('logo');
        $this->assertNull($sponsor->fresh()->logo_path);
    }

    public function test_workspace_can_save_edit_and_clear_a_footer_style_sponsor_link(): void
    {
        $facebookUrl = 'https://www.facebook.com/communitypartner';
        $this->actingAs($this->actor());
        $this->get(route('workspace.sponsors.create'))->assertOk()
            ->assertSee('Facebook page or website link (optional)');
        $this->post(route('workspace.sponsors.store'), $this->data(['website_url' => $facebookUrl]))
            ->assertSessionHasNoErrors()->assertRedirect(route('workspace.sponsors.index'));
        $sponsor = Sponsor::firstOrFail();

        foreach (['workspace.sponsors.index', 'home'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $link = '//a[@href="'.$facebookUrl.'"][@class="footer-contact-button"]';
            $this->assertSame('Community Partner on Facebook', $xpath->evaluate('normalize-space('.$link.')'));
            $this->assertSame('_blank', $xpath->evaluate('string('.$link.'/@target)'));
            $this->assertSame(1.0, $xpath->evaluate('count('.$link.'/svg/path[@fill="currentColor"])'));
        }

        $this->get(route('workspace.sponsors.edit', $sponsor))->assertOk()
            ->assertSee('value="'.$facebookUrl.'"', false);
        $websiteUrl = 'https://example.com/?ref=facebook.com';
        $this->put(route('workspace.sponsors.update', $sponsor), $this->data(['website_url' => $websiteUrl]))
            ->assertSessionHasNoErrors()->assertRedirect(route('workspace.sponsors.index'));
        $this->get(route('home'))->assertOk()->assertSee('Visit Community Partner online')->assertSee($websiteUrl)
            ->assertDontSee('Community Partner on Facebook');

        $this->put(route('workspace.sponsors.update', $sponsor), $this->data(['website_url' => '']))
            ->assertSessionHasNoErrors()->assertRedirect(route('workspace.sponsors.index'));
        $this->assertNull($sponsor->fresh()->website_url);
        $this->get(route('home'))->assertOk()->assertDontSee('Visit Community Partner online');
    }

    public function test_home_and_mobile_only_display_visible_sponsors_in_order(): void
    {
        Sponsor::create($this->data(['name' => 'Hidden Partner', 'is_active' => 0]));
        Sponsor::create($this->data(['name' => 'Later Partner', 'sort_order' => 5]));
        Sponsor::create($this->data(['name' => 'First Partner', 'website_url' => null]));
        $user = $this->actor('student', '');
        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSeeInOrder(['First Partner', 'Later Partner'])->assertDontSee('Hidden Partner')->assertDontSee('Sponsor 1');
        Cache::put('mobile_access:sponsor-test', $user->getKey(), now()->addHour());
        $token = Crypt::encryptString(json_encode(['sub' => $user->getKey(), 'jti' => 'sponsor-test', 'exp' => now()->addHour()->timestamp]));
        $this->withToken($token)->getJson('/api/mobile/v1/home')->assertOk()
            ->assertJsonCount(2, 'data.sponsors')->assertJsonPath('data.sponsors.0.name', 'First Partner')
            ->assertJsonPath('data.sponsors.0.website_url', null)->assertDontSee('Hidden Partner');
    }

    public function test_empty_and_pre_migration_home_still_render(): void
    {
        $this->actingAs($this->actor('student', ''))->get(route('home'))->assertOk()->assertSee('Our partners and sponsors will be announced soon.');
        (require database_path('migrations/2026_11_14_000002_create_sponsors_table.php'))->down();
        $this->get(route('home'))->assertOk()->assertSee('Our partners and sponsors will be announced soon.');
    }

}
