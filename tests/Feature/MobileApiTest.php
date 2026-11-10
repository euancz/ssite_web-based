<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function token(): string
    {
        $user = User::forceCreate([
            'name' => 'Mobile Student', 'email' => 'mobile@example.test',
            'role' => 'student', 'profile_completed_at' => now(),
        ]);
        Cache::put('mobile_access:test-session', $user->getKey(), now()->addHour());
        return Crypt::encryptString(json_encode([
            'sub' => $user->getKey(), 'jti' => 'test-session', 'exp' => now()->addHour()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function article(string $title, string $status = 'approved'): Article
    {
        return Article::forceCreate([
            'title' => $title, 'content' => 'Published article body',
            'approval_status' => $status, 'content_status' => 'active',
        ]);
    }

    public function test_mobile_content_requires_a_session(): void
    {
        $this->getJson('/api/mobile/v1/home')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/documents')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/about')->assertUnauthorized();
    }

    public function test_home_and_feed_share_the_cms_publication_boundary(): void
    {
        $published = $this->article('Published');
        $this->article('Pending secret', 'pending');
        $archived = $this->article('Archived secret');
        $archived->forceFill(['content_status' => 'archived'])->save();

        $this->withToken($this->token())->getJson('/api/mobile/v1/home')
            ->assertOk()->assertJsonCount(1, 'data.articles')
            ->assertJsonPath('data.articles.0.id', $published->getKey())
            ->assertJsonPath('data.articles.0.module', 'articles')
            ->assertJsonPath('data.articles.0.author', 'Former member')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->getJson('/api/mobile/v1/articles')->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_search_sort_and_pagination_are_applied_on_the_server(): void
    {
        $this->article('CMS Beta');
        $this->article('CMS Alpha');
        $this->article('Other');
        $this->withToken($this->token())->getJson('/api/mobile/v1/articles?q=CMS&sort=title&per_page=1')
            ->assertOk()->assertJsonPath('data.0.title', 'CMS Alpha')
            ->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/mobile/v1/articles?q=CMS&sort=title&per_page=1&page=2')
            ->assertOk()->assertJsonPath('data.0.title', 'CMS Beta');
    }

    public function test_about_uses_the_same_current_year_as_the_website(): void
    {
        $this->withToken($this->token())->getJson('/api/mobile/v1/about')
            ->assertOk()->assertJsonPath('data.academic_year', \App\Support\AcademicYear::current())
            ->assertJsonCount(0, 'data.officers');
    }

    public function test_published_pdfs_require_auth_and_unpublished_files_stay_hidden(): void
    {
        Storage::fake('local');
        $path = 'documents/' . str_repeat('a', 40) . '.pdf';
        $this->assertTrue(Storage::disk('local')->put($path, '%PDF-1.7 test'));
        $document = Document::forceCreate([
            'title' => 'Official document', 'approval_status' => 'approved', 'content_status' => 'active',
            'file_path' => $path, 'original_name' => 'official.pdf',
        ]);
        $url = '/api/mobile/v1/files/documents/' . $document->getKey();
        $this->getJson($url)->assertUnauthorized();
        $this->withToken($this->token())->getJson('/api/mobile/v1/documents')
            ->assertOk()->assertJsonPath('data.0.module', 'documents')
            ->assertJsonPath('data.0.file_url', route('api.mobile.file', ['module' => 'documents', 'id' => $document->getKey()]));
        $response = $this->getJson($url);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->withHeader('Range', 'bytes=0-4')->get($url)->assertStatus(206);
        $document->forceFill(['approval_status' => 'pending'])->save();
        $this->get($url)->assertNotFound();
        $this->getJson('/api/mobile/v1/documents')->assertJsonPath('meta.total', 0);
    }
}
