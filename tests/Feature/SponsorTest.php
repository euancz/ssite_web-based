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
        foreach ([['student', 'President'], ['officer', 'Secretary'], ['adviser', ''], ['superadmin', '']] as [$role, $position]) {
            $this->actingAs($this->actor($role, $position));
            $this->get(route('workspace.sponsors.index'))->assertForbidden();
            $this->get(route('workspace.sponsors.create'))->assertForbidden();
            $this->get(route('workspace.sponsors.edit', $sponsor))->assertForbidden();
            $this->post(route('workspace.sponsors.store'), $this->data())->assertForbidden();
            $this->put(route('workspace.sponsors.update', $sponsor), $this->data())->assertForbidden();
            $this->delete(route('workspace.sponsors.destroy', $sponsor))->assertForbidden();
        }
        $this->actingAs($this->actor())->get(route('workspace.sponsors.index'))->assertOk()->assertSee('Add sponsor');
        $this->get(route('workspace.sponsors.create'))->assertOk();
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

    public function test_shared_mobile_feed_tracks_sponsor_changes_and_visibility(): void
    {
        (require database_path('migrations/2026_11_14_000001_create_mobile_publications.php'))->up();
        Storage::fake('mobile');
        config(['mobile.sync_enabled' => true]);
        $sponsor = Sponsor::create($this->data());
        $this->assertDatabaseHas('mobile_publications', ['module' => 'sponsors', 'record_id' => $sponsor->id]);
        $sponsor->update(['name' => 'Updated Partner', 'sort_order' => 9]);
        $row = DB::table('mobile_publications')->where('module', 'sponsors')->first();
        $this->assertSame('Updated Partner', json_decode($row->payload, true)['name']);
        $this->assertSame(9, $row->sort_order);
        $sponsor->update(['is_active' => false]);
        $this->assertDatabaseCount('mobile_publications', 0);
        $sponsor->update(['is_active' => true]);
        $sponsor->delete();
        $this->assertDatabaseCount('mobile_publications', 0);
    }
}
