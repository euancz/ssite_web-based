<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfilePictures;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use BuildsContentSchema;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function user(): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Member', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'student',
            'profile_picture' => 'profile-pictures/original.png', 'profile_completed_at' => now()])->save();
        Storage::disk('public')->put($user->profile_picture, base64_decode(self::PNG));
        return $user;
    }

    private function upload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('picture.png', base64_decode(self::PNG));
    }

    public function test_storage_failure_preserves_the_existing_picture(): void
    {
        $user = $this->user();
        $originalDisk = Storage::disk('public');
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        try {
            app(ProfilePictures::class)->replace($user, $this->upload());
            $this->fail('Storage failure must be reported.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('picture', $exception->errors());
            $this->assertSame('profile-pictures/original.png', $user->fresh()->profile_picture);
            $originalDisk->assertExists('profile-pictures/original.png');
        }
    }

    public function test_database_failure_preserves_the_old_picture_and_cleans_up_the_new_upload(): void
    {
        $user = $this->user();
        DB::statement("CREATE TRIGGER reject_picture BEFORE UPDATE ON users BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            app(ProfilePictures::class)->replace($user, $this->upload());
            $this->fail('Database failure must not report success.');
        } catch (QueryException) {
            $this->assertSame('profile-pictures/original.png', $user->fresh()->profile_picture);
            $this->assertSame(['profile-pictures/original.png'], Storage::disk('public')->allFiles('profile-pictures'));
        }
    }

    public function test_failed_picture_removal_preserves_the_file_and_successful_replacement_revokes_it(): void
    {
        $user = $this->user();
        DB::statement("CREATE TRIGGER reject_picture BEFORE UPDATE ON users BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            app(ProfilePictures::class)->replace($user, null);
            $this->fail('Database failure must not delete the old file.');
        } catch (QueryException) {
            Storage::disk('public')->assertExists('profile-pictures/original.png');
        }
        DB::statement('DROP TRIGGER reject_picture');
        app(ProfilePictures::class)->replace($user, $this->upload());
        Storage::disk('public')->assertMissing('profile-pictures/original.png');
        Storage::disk('public')->assertExists($user->fresh()->profile_picture);
        app(ProfilePictures::class)->replace($user, null);
        $this->assertNull($user->fresh()->profile_picture);
        $this->assertSame([], Storage::disk('public')->allFiles('profile-pictures'));
    }

    public function test_security_headers_protect_pages_and_unauthenticated_api_responses(): void
    {
        foreach (['/' => 200, '/api/mobile/v1/home' => 401] as $url => $status) {
            $this->get($url)->assertStatus($status)->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_production_redirects_http_to_the_configured_https_host_and_sets_hsts(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://campus.example']);
        $this->get('http://untrusted.example/articles?q=hello')
            ->assertStatus(308)->assertRedirect('https://campus.example/articles?q=hello');
        $this->get('https://campus.example/')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_production_with_no_https_configuration_fails_closed(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'http://campus.example']);
        $this->get('/')->assertStatus(503);
    }

    public function test_production_configuration_disables_debug_and_requires_secure_cookies(): void
    {
        $code = "require 'vendor/autoload.php'; new \Illuminate\Foundation\Application(getcwd()); echo json_encode([(require 'config/app.php')['debug'], (require 'config/session.php')['secure']]);";
        $process = new \Symfony\Component\Process\Process([PHP_BINARY, '-r', $code], base_path(), [
            'APP_ENV' => 'production', 'APP_DEBUG' => 'true', 'SESSION_SECURE_COOKIE' => 'false',
        ]);
        $process->mustRun();
        $this->assertSame([false, true], json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    }
}
