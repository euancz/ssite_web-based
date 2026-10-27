<?php

namespace Tests\Feature;

use App\Models\ContentDraft;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class DocumentAcademicYearTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        Storage::fake('local');
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_27_000002_add_academic_year_to_documents_table.php');
    }

    private function actor(): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Secretary', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => 'Secretary', 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function record(User $owner, array $values = []): int
    {
        return DB::table('documents')->insertGetId(array_merge(['title' => 'Document fixture', 'created_by_user_id' => $owner->user_id,
            'approval_status' => 'approved', 'content_status' => 'active', 'created_at' => '2026-10-01 12:00:00',
            'file_path' => 'documents/private.pdf', 'original_name' => 'record.pdf', 'file_size' => 100], $values), 'document_id');
    }

    public function test_migration_preserves_records_and_leaves_existing_years_unassigned(): void
    {
        $owner = $this->actor();
        $id = $this->record($owner);
        $before = DB::table('documents')->where('document_id', $id)->first();
        $migration = $this->migration();
        $migration->up();
        $this->assertNull(DB::table('documents')->value('academic_year'));
        $this->assertSame($before->created_at, DB::table('documents')->value('created_at'));
        $this->assertSame('approved', DB::table('documents')->value('approval_status'));
        $this->assertTrue(collect(Schema::getIndexes('documents'))->contains(fn ($index) => $index['name'] === 'documents_academic_year_index'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('documents', 'academic_year'));
        $this->assertEquals($before, DB::table('documents')->where('document_id', $id)->first());
    }

    public function test_public_year_choices_and_results_do_not_reveal_private_records(): void
    {
        $this->migration()->up();
        $owner = $this->actor();
        $this->record($owner, ['title' => 'Public assigned', 'academic_year' => '2026-2027']);
        $this->record($owner, ['title' => 'Public unknown']);
        $this->record($owner, ['title' => 'Private pending', 'academic_year' => '2024-2025', 'approval_status' => 'pending']);
        $this->record($owner, ['title' => 'Private archived', 'academic_year' => '2023-2024', 'content_status' => 'archived']);
        $response = $this->get(route('documents.index', ['academic_year' => '2026-2027']))->assertOk()
            ->assertSee('Public assigned')->assertDontSee('Public unknown')->assertDontSee('2024-2025')->assertDontSee('2023-2024');
        $this->assertSame(['', 'unassigned', '2026-2027'], array_keys($response->viewData('academicYearOptions')));
        $this->get(route('documents.index', ['academic_year' => 'unassigned']))->assertOk()->assertSee('Public unknown')->assertDontSee('Public assigned');
        $this->get(route('documents.index', ['academic_year' => ['bad']]))->assertOk()->assertSee('Public assigned');
    }

    public function test_workspace_filters_are_owner_scoped_and_preserved_in_sort_and_pagination_links(): void
    {
        $this->migration()->up();
        $owner = $this->actor();
        $other = $this->actor();
        for ($i = 0; $i < 6; $i++) {
            $this->record($owner, ['title' => 'Minutes '.$i, 'category' => 'Records', 'semester' => 2, 'academic_year' => '2026-2027']);
        }
        $this->record($owner, ['title' => 'Wrong year', 'academic_year' => '2025-2026']);
        $this->record($other, ['title' => 'Other owner', 'academic_year' => '2020-2021']);
        $filters = ['tab' => 'mine', 'q' => 'Minutes', 'category' => 'Records', 'semester' => '2', 'academic_year' => '2026-2027'];
        $response = $this->actingAs($owner)->get(route('workspace.posts.documents.index', $filters))->assertOk()->assertDontSee('Other owner')->assertDontSee('2020-2021');
        $this->assertSame(6, $response->viewData('documents')->total());
        $this->assertStringContainsString('academic_year=2026-2027', $response->viewData('documents')->nextPageUrl());
        $response->assertSee(route('workspace.posts.documents.index', ['tab' => 'mine', 'sort' => 'title', 'q' => 'Minutes', 'academic_year' => '2026-2027', 'semester' => '2', 'category' => 'Records']));
    }

    public function test_year_validation_allows_old_consecutive_years_preserves_omitted_values_and_can_clear_assignment(): void
    {
        $this->migration()->up();
        $owner = $this->actor();
        $id = $this->record($owner);
        $this->actingAs($owner);
        foreach (['2026-2028', '2027-2026', '0000-0001', '<script>', ['2026-2027']] as $invalid) {
            $this->patch(route('documents.update', $id), ['title' => 'Valid title', 'academic_year' => $invalid])->assertSessionHasErrors('academic_year');
        }
        // Start a fresh upload-limit window after exercising invalid requests.
        $this->travel(61)->seconds();
        $this->patch(route('documents.update', $id), ['title' => 'Historic record', 'academic_year' => '1999-2000'])->assertSessionHasNoErrors()->assertRedirect(route('workspace.posts.documents.show', $id));
        $this->assertSame('1999-2000', DB::table('documents')->value('academic_year'));
        $this->patch(route('documents.update', $id), ['title' => 'Renamed record'])->assertSessionHasNoErrors()->assertRedirect(route('workspace.posts.documents.show', $id));
        $this->assertSame('1999-2000', DB::table('documents')->value('academic_year'));
        $this->patch(route('documents.update', $id), ['title' => 'Unknown year', 'academic_year' => ''])->assertSessionHasNoErrors()->assertRedirect(route('workspace.posts.documents.show', $id));
        $this->assertNull(DB::table('documents')->value('academic_year'));
    }

    public function test_year_survives_draft_save_resume_and_normal_pdf_submission(): void
    {
        $this->migration()->up();
        $this->actingAs($this->actor())->post(route('workspace.drafts.save', 'documents'), ['title' => 'Annual report', 'academic_year' => '2025-2026'])->assertRedirect();
        $draft = ContentDraft::firstOrFail();
        $this->assertSame('2025-2026', $draft->payload['academic_year']);
        $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertSee('value="2025-2026"', false);
        $pdf = UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->post(route('documents.store'), ['draft_id' => $draft->getKey(), 'draft_version' => 1,
            'title' => 'Annual report', 'academic_year' => '2025-2026', 'file' => $pdf])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2025-2026', DB::table('documents')->value('academic_year'));
        $this->assertSame('pending', DB::table('documents')->value('approval_status'));
        $this->assertSame(0, ContentDraft::count());
    }

    public function test_feature_remains_hidden_and_existing_workflows_work_before_migration(): void
    {
        $owner = $this->actor();
        $id = $this->record($owner);
        $this->get(route('documents.index'))->assertOk()->assertDontSee('name="academic_year"', false);
        $this->actingAs($owner)->get(route('documents.create'))->assertOk()->assertDontSee('name="academic_year"', false);
        $this->patch(route('documents.update', $id), ['title' => 'Still editable', 'academic_year' => '2026-2027'])->assertSessionHasNoErrors();
        $this->assertSame('Still editable', DB::table('documents')->value('title'));
        $this->post(route('workspace.drafts.save', 'documents'), ['title' => 'Draft still works', 'academic_year' => '2026-2027'])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('academic_year', ContentDraft::firstOrFail()->payload);
    }
}
