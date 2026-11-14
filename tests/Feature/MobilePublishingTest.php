<?php

namespace Tests\Feature;

use App\Models\{Article, Document};
use App\Services\MobilePublisher;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MobilePublishingTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mobile.sync_enabled' => false]);
        $this->buildContentSchema();
        (require database_path('migrations/2026_11_14_000001_create_mobile_publications.php'))->up();
        Storage::fake('mobile');
        Storage::fake('local');
        config(['mobile.sync_enabled' => true]);
    }

    public function test_saving_and_archiving_content_keeps_only_published_app_data(): void
    {
        $article = Article::forceCreate(['title' => 'Published', 'content' => 'Visible body',
            'approval_status' => 'approved', 'content_status' => 'active']);
        $payload = json_decode(DB::table('mobile_publications')->sole()->payload, true);
        $this->assertSame('Visible body', $payload['description']);
        $this->assertArrayNotHasKey('url', $payload);
        $this->assertArrayNotHasKey('image_url', $payload);
        $article->forceFill(['content_status' => 'archived'])->save();
        $this->assertSame(0, DB::table('mobile_publications')->count());
        $article->forceFill(['content_status' => 'active'])->save();
        $this->assertSame(1, DB::table('mobile_publications')->count());
        $article->delete();
        $this->assertSame(0, DB::table('mobile_publications')->count());
        Article::forceCreate(['title' => 'Pending', 'content' => 'Secret']);
        $this->assertSame(0, DB::table('mobile_publications')->count());
    }

    public function test_pdf_is_copied_to_private_cloud_storage_without_a_localhost_url(): void
    {
        $path = 'documents/'.str_repeat('a', 40).'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.7 sample');
        Document::forceCreate(['title' => 'Report', 'description' => 'Published report',
            'approval_status' => 'approved', 'content_status' => 'active',
            'file_path' => $path, 'original_name' => 'report.pdf']);
        $payload = json_decode(DB::table('mobile_publications')->sole()->payload, true);
        $this->assertStringStartsWith('published/', $payload['file_path']);
        Storage::disk('mobile')->assertExists($payload['file_path']);
        $this->assertArrayNotHasKey('file_url', $payload);
        $this->assertStringNotContainsString('localhost', json_encode($payload));
    }

    public function test_publication_preserves_safe_rich_content(): void
    {
        (require database_path('migrations/2026_10_27_000004_add_content_format_to_articles_table.php'))->up();
        Article::forceCreate(['title' => 'Formatted', 'content' => '**Bold**', 'content_format' => 'markdown',
            'approval_status' => 'approved', 'content_status' => 'active']);
        $payload = json_decode(DB::table('mobile_publications')->sole()->payload, true);
        $this->assertStringContainsString('<strong>Bold</strong>', $payload['content_html']);
    }
}
