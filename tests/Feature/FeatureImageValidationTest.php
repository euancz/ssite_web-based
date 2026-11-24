<?php

namespace Tests\Feature;

use App\Http\Requests\Content\AchievementRequest;
use App\Http\Requests\Content\ActivityRequest;
use App\Http\Requests\Content\ArticleRequest;
use App\Http\Requests\SaveContentDraftRequest;
use App\Models\OfficerTerm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class FeatureImageValidationTest extends TestCase
{
    use BuildsContentSchema;

    public function test_officer_photo_attachments_validate_the_image_and_extension_on_create_and_edit(): void
    {
        $this->buildContentSchema();
        $this->withoutVite();
        Storage::fake('public');

        $president = new User;
        $president->forceFill([
            'name' => 'Alex Rivera', 'email' => 'alex@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => 'President', 'profile_completed_at' => now(),
        ])->save();
        $this->actingAs($president);

        $this->get(route('adviser.officers.create'))->assertOk()
            ->assertSee('Attach an officer photo')->assertSee('accept=".jpg,.jpeg,.png"', false);

        $fields = ['academic_year' => '2026-2027', 'position' => 'President', 'name' => 'Alex Rivera', 'sort_order' => 0];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA');
        $files = [];
        $upload = function (string $name, string $content) use (&$files): UploadedFile {
            $file = UploadedFile::fake()->createWithContent($name, $content);
            $files[] = $file;

            return new UploadedFile($file->path(), $name, test: true);
        };

        $this->post(route('adviser.officers.store'), $fields + ['photo' => $upload('photo.webp', $png)])
            ->assertSessionHasErrors('photo');
        $this->post(route('adviser.officers.store'), $fields + ['photo' => $upload('photo.png', $png)])
            ->assertSessionHasNoErrors();

        $officer = OfficerTerm::sole();
        Storage::disk('public')->assertExists($officer->photo);
        $this->get(route('adviser.officers.edit', $officer))->assertOk()
            ->assertSee('Replace officer photo (optional)')->assertSee('officer-photo-preview');

        $this->patch(route('adviser.officers.update', $officer), $fields + ['photo' => $upload('photo.png', $webp)])
            ->assertSessionHasErrors('photo');
        $this->assertSame($officer->photo, $officer->fresh()->photo);

        $jpg = file_get_contents(public_path('images/rawri-26-27-community.jpg'));
        $this->patch(route('adviser.officers.update', $officer), $fields + ['photo' => $upload('photo.jpeg', $jpg)])
            ->assertSessionHasNoErrors();
        $this->assertNotSame($officer->photo, $officer->fresh()->photo);
    }

    public function test_feature_images_require_jpg_or_png_content_and_extensions_in_posts_and_drafts(): void
    {
        $this->buildContentSchema();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $jpg = file_get_contents(public_path('images/rawri-26-27-community.jpg'));
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $handles = [];
        $upload = function (string $name, string $content) use (&$handles): UploadedFile {
            $file = tmpfile();
            fwrite($file, $content);
            $handles[] = $file;

            return new UploadedFile(stream_get_meta_data($file)['uri'], $name, null, null, true);
        };

        foreach (['articles' => ArticleRequest::class, 'activities' => ActivityRequest::class, 'achievements' => AchievementRequest::class] as $module => $requestClass) {
            $draft = new SaveContentDraftRequest;
            $route = (new Route('POST', 'drafts/{module}', fn () => null))
                ->bind(Request::create('/drafts/'.$module, 'POST'));
            $draft->setRouteResolver(fn () => $route);

            foreach ([new $requestClass, $draft] as $request) {
                $rules = ['image' => $request->rules()['image']];
                foreach (['photo.png' => $png, 'photo.jpg' => $jpg] as $name => $content) {
                    $this->assertTrue(Validator::make(['image' => $upload($name, $content)], $rules)->passes(), $module.' '.$name);
                }
                foreach (['photo.webp' => $png, 'photo.jpeg' => $jpg, 'photo.jpg' => $gif, 'photo.png' => 'not an image'] as $name => $content) {
                    $this->assertTrue(Validator::make(['image' => $upload($name, $content)], $rules)->fails(), $module.' '.$name);
                }
                $oversized = $upload('large.png', $png.str_repeat(' ', 2048 * 1024));
                $this->assertTrue(Validator::make(['image' => $oversized], $rules)->fails());
            }
        }
    }
}
