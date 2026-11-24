<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSectionEditorTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role, 'profile_completed_at' => now()]));
    }

    public function test_only_advisers_can_open_the_editor_or_write_changes(): void
    {
        $url = route('adviser.site.edit', 'home-news');
        $this->get('/')->assertDontSee('class="section-pencil', false);
        $this->put($url)->assertRedirect();
        foreach (['admin', 'officer', 'student'] as $role) {
            $this->signIn($role);
            $this->get($url)->assertForbidden();
            $this->put($url)->assertForbidden();
            $this->get(route('policies'))->assertDontSee('class="section-pencil', false);
        }
        $this->assertDatabaseCount('site_sections', 0);
    }

    public function test_adviser_can_publish_text_and_images_and_stale_edits_are_rejected(): void
    {
        Storage::fake('public');
        $this->signIn('adviser');
        $section = 'landing-welcome';
        $url = route('adviser.site.edit', $section);
        $this->get(route('adviser.site.landing'))->assertOk()->assertSee($url);
        $this->get($url)->assertOk()->assertSee('Save changes');
        $fields = array_filter(config("site_sections.$section.fields"), fn ($key) => ! str_ends_with($key, '_image'), ARRAY_FILTER_USE_KEY);
        $fields['headline'] = 'Campus <strong>stories</strong>';
        $fields['mascot_image'] = UploadedFile::fake()->createWithContent('mascot.png', file_get_contents(public_path('images/ssite-favicon.png')));
        $this->put($url, ['revision' => 0, 'fields' => $fields])->assertSessionHasNoErrors()->assertRedirect();
        unset($fields['mascot_image']);
        $this->put($url, ['revision' => 0, 'fields' => $fields])->assertSessionHasErrors('revision');

        $files = Storage::disk('public')->allFiles('site-sections');
        $this->assertCount(1, $files);
        auth()->logout();
        $this->get('/')->assertOk()->assertSee($fields['headline'])
            ->assertSee(Storage::disk('public')->url($files[0]))->assertDontSee('class="section-pencil', false);
    }

    public function test_students_see_the_published_mission_without_edit_controls(): void
    {
        $this->signIn('adviser');
        $url = route('adviser.site.update', 'about-mission');
        $this->get(route('about'))->assertOk()->assertSee($url);
        $fields = ['heading' => 'Our mission', 'description' => 'Learn together and serve our campus.'];
        $this->put($url, ['revision' => 0, 'fields' => $fields])->assertSessionHasNoErrors();
        $fields['description'] = 'Build and learn together.';
        $this->put($url, ['revision' => 1, 'fields' => $fields])->assertSessionHasNoErrors();

        $this->signIn('student');
        $this->get(route('about'))->assertOk()->assertSee($fields['description'])->assertDontSee($url);
    }

    public function test_unknown_sections_and_unsafe_links_are_rejected(): void
    {
        $this->signIn('adviser');
        $this->get(route('adviser.site.edit', 'unknown'))->assertNotFound();
        $this->put(route('adviser.site.update', 'app-footer'), [
            'revision' => 0, 'fields' => ['map_url' => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('fields.map_url');
        $this->assertDatabaseCount('site_sections', 0);
    }
}
