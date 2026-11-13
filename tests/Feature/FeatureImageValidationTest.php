<?php

namespace Tests\Feature;

use App\Http\Requests\Content\AchievementRequest;
use App\Http\Requests\Content\ActivityRequest;
use App\Http\Requests\Content\ArticleRequest;
use App\Http\Requests\SaveContentDraftRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class FeatureImageValidationTest extends TestCase
{
    use BuildsContentSchema;

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
