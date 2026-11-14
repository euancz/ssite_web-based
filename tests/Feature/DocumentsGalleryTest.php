<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Concerns\BuildsContentSchema;
use App\Models\User;

class DocumentsGalleryTest extends TestCase
{
    use BuildsContentSchema;
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]));
        foreach ([
            ['First semester record', 'Activities', 1, 'approved'],
            ['Second semester record', 'Activities', 2, 'approved'],
            ['Other category record', 'Minutes', 1, 'approved'],
            ['Pending private record', 'Activities', 1, 'pending'],
            ['Unassigned record', 'Activities', null, 'approved'],
        ] as [$title, $category, $semester, $status]) {
            DB::table('documents')->insert([
                'title' => $title, 'category' => $category, 'semester' => $semester,
                'file_path' => 'documents/'.str_repeat('a', 40).'.pdf', 'original_name' => 'fixture.pdf', 'file_size' => 100,
                'approval_status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_search_semester_and_category_filters_combine_without_exposing_pending_records(): void
    {
        $this->get('/documents?q=record&semester=1&category=Activities')->assertOk()
            ->assertSee('First semester record')
            ->assertDontSee('Second semester record')
            ->assertDontSee('Other category record')
            ->assertDontSee('Pending private record')
            ->assertDontSee('Unassigned record')
            ->assertSee('Official records')
            ->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('Document Preview')
            ->assertSee('data-document-preview=', false)
            ->assertDontSee('Sign in to preview this document.');
    }

    public function test_all_semesters_include_unassigned_records_and_invalid_semesters_are_ignored(): void
    {
        $this->get('/documents?semester=invalid')->assertOk()
            ->assertSee('First semester record')
            ->assertSee('Second semester record')
            ->assertSee('Unassigned record')
            ->assertDontSee('Pending private record');
    }

    public function test_search_has_an_empty_state_and_reset_link(): void
    {
        $this->get('/documents?q=nonexistent-title')->assertOk()
            ->assertSee('No matching documents')
            ->assertSee('Clear filters');
    }
}
