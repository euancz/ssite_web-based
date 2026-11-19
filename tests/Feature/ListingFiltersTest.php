<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ListingFiltersTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]));
    }

    private function report(string $title, string $date, int $amount, string $status = 'approved'): void
    {
        DB::table('liquidations')->insert([
            'title' => $title, 'report_date' => $date, 'amount' => $amount,
            'approval_status' => $status, 'file_path' => 'reports/fixture.pdf',
            'original_name' => 'fixture.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_report_search_year_and_amount_sort_combine_with_public_visibility(): void
    {
        $this->report('Workshop high', '2026-02-01', 900);
        $this->report('Workshop low', '2026-03-01', 100);
        $this->report('Workshop previous', '2025-03-01', 50);
        $this->report('Unrelated', '2026-03-01', 75);
        $this->report('Private workshop', '2024-03-01', 10, 'pending');
        $response = $this->get('/transparency?q=Workshop&year=2026&sort=amount_low')->assertOk();
        $this->assertSame(['Workshop low', 'Workshop high'], $response->viewData('liquidations')->pluck('title')->all());
        $this->getJson('/transparency?_suggest=1&q=wORK&year=2026&sort=amount_low')
            ->assertOk()->assertExactJson(['results' => ['Workshop low', 'Workshop high']]);
        $this->assertArrayNotHasKey('2024', $response->viewData('yearOptions'));
        $response->assertSee('Report year')->assertSee('Amount (low to high)')->assertDontSee('Sort by semester');
    }

    public function test_report_filter_state_survives_pagination_and_invalid_values_fall_back(): void
    {
        for ($i = 0; $i < 7; $i++) $this->report('Workshop '.$i, '2026-01-01', $i);
        $response = $this->get('/transparency?q=Workshop&year=2026&sort=amount_low')->assertOk();
        $url = $response->viewData('liquidations')->nextPageUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame('2026', $params['year']);
        $this->assertSame('Workshop', $params['q']);
        $this->assertSame('amount_low', $params['sort']);
        $this->get('/transparency?year[]=2026&sort[]=bad')->assertOk()->assertViewHas('year', '')->assertViewHas('sort', 'recent');
    }

    public function test_discover_filters_all_records_before_limiting_and_excludes_private_content(): void
    {
        foreach (range(1, 5) as $i) {
            DB::table('articles')->insert(['title' => 'New story '.$i, 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('articles')->insert(['title' => 'Matching story', 'content' => 'Student workshop', 'approval_status' => 'approved', 'created_at' => now()->subDays(2), 'updated_at' => now()]);
        DB::table('articles')->insert(['title' => 'Private story', 'content' => 'Student workshop', 'approval_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('articles')->insert(['title' => 'Old story', 'content' => 'Student workshop', 'approval_status' => 'approved', 'created_at' => now()->subDays(40), 'updated_at' => now()]);
        $response = $this->get('/discover?type=articles&q=workshop&period=week&sort=title')->assertOk();
        $this->assertSame(['Matching story'], $response->viewData('articles')->pluck('title')->all());
        $this->assertCount(0, $response->viewData('activities'));
        $response->assertSee('Matching story')->assertDontSee('Private story')->assertSee('Clear filters');
        $this->getJson('/discover?_suggest=1&type=articles&q=wORKSH&period=week')
            ->assertOk()->assertExactJson(['results' => ['Matching story']]);
    }

    public function test_document_search_filters_keep_requested_title_order(): void
    {
        foreach (['Zulu minutes', 'Alpha minutes'] as $title) {
            DB::table('documents')->insert(['title' => $title, 'category' => 'Minutes', 'semester' => 1, 'approval_status' => 'approved', 'file_path' => 'documents/fixture.pdf', 'original_name' => 'fixture.pdf', 'created_at' => now(), 'updated_at' => now()]);
        }
        $response = $this->get('/documents?q=minutes&semester=1&category=Minutes&sort=title')->assertOk();
        $this->assertSame(['Alpha minutes', 'Zulu minutes'], $response->viewData('documents')->pluck('title')->all());
        $this->getJson('/documents?_suggest=1&q=MIN&semester=1&category=Minutes&sort=title')
            ->assertOk()->assertExactJson(['results' => ['Alpha minutes', 'Zulu minutes']]);
        $response->assertSee('name="sort"', false)->assertDontSee('Sort By Semester');
    }
    public function test_public_keyword_search_combines_with_activity_timeframe_without_leaking_private_records(): void
    {
        foreach ([['Workshop tomorrow', 'approved', 1], ['Workshop yesterday', 'approved', -1], ['Private workshop', 'pending', 1], ['Other event', 'approved', 1]] as [$title, $status, $offset]) {
            DB::table('activities')->insert(['title' => $title, 'description' => 'Event details', 'approval_status' => $status, 'activity_date' => today()->addDays($offset), 'created_at' => now(), 'updated_at' => now()]);
        }
        $response = $this->get('/activities?q=workshop&timeframe=upcoming&sort=event_soonest')->assertOk();
        $this->assertSame(['Workshop tomorrow'], $response->viewData('activities')->pluck('title')->all());
        $response->assertSee('Search posts')->assertSee('Clear filters');
    }

    public function test_listing_search_terms_stay_out_of_the_global_header_search(): void
    {
        foreach (['/discover', '/documents', '/activities'] as $path) {
            $response = $this->get($path.'?q=workshop')->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $headerInputs = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " search-form ")]/input[@name="q"]');
            $this->assertSame(2, $headerInputs->length);
            foreach ($headerInputs as $input) {
                $this->assertSame('', $input->getAttribute('value'));
            }
            $localInputs = $xpath->query('//main//input[@name="q" and @value="workshop"]');
            $this->assertGreaterThan(0, $localInputs->length);
            $response->assertSee('site-filter-reset')->assertSee('search-controls.js');
        }
    }

    public function test_header_search_covers_public_content_while_discover_respects_its_selected_type(): void
    {
        foreach (['articles' => 'Workshop story', 'activities' => 'Workshop event', 'documents' => 'Workshop minutes'] as $table => $title) {
            DB::table($table)->insert(array_merge(['title' => $title, 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()], $table === 'documents' ? ['file_path' => 'documents/fixture.pdf', 'original_name' => 'fixture.pdf'] : []));
        }
        DB::table('articles')->insert(['title' => 'Workshop private', 'approval_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $global = $this->get(route('search.index', ['q' => 'workshop']))->assertOk();
        $this->assertEqualsCanonicalizing(['articles', 'activities', 'documents'], collect($global->viewData('results')->items())->pluck('type')->all());
        $global->assertDontSee('Workshop private');
        $global->assertSee('aria-controls="results-search-list"', false);
        $this->getJson(route('search.suggest', ['q' => 'wORKsh']))
            ->assertOk()->assertJsonCount(3, 'results')->assertJsonMissing(['title' => 'Workshop private']);
        $local = $this->get('/discover?q=workshop&type=articles')->assertOk();
        $this->assertSame(['Workshop story'], $local->viewData('articles')->pluck('title')->all());
        $this->assertCount(0, $local->viewData('activities'));
        $this->assertCount(0, $local->viewData('achievements'));
        $local->assertDontSee('Workshop minutes')->assertDontSee('Workshop private');
    }
    public function test_sponsor_visibility_and_keyword_filters_keep_name_order(): void
    {
        (require database_path('migrations/2026_11_14_000002_create_sponsors_table.php'))->up();
        $manager = User::forceCreate(['name' => 'Manager', 'email' => 'filters@mcc.edu.ph', 'role' => 'officer', 'officer_position' => 'President', 'profile_completed_at' => now()]);
        $this->actingAs($manager);
        foreach ([['Zulu partner', true], ['Alpha partner', true], ['Hidden partner', false], ['Unrelated', true]] as [$name, $visible]) {
            \App\Models\Sponsor::create(['name' => $name, 'description' => 'Community support', 'is_active' => $visible, 'sort_order' => 0]);
        }
        $response = $this->get(route('workspace.sponsors.index', ['q' => 'partner', 'visibility' => 'visible', 'sort' => 'name']))->assertOk();
        $this->assertSame(['Alpha partner', 'Zulu partner'], $response->viewData('sponsors')->pluck('name')->all());
        $response->assertDontSee('Hidden partner')->assertSee('Find sponsors');
        $this->getJson(route('workspace.sponsors.index', ['_suggest' => 1, 'q' => 'pARTN', 'visibility' => 'visible', 'sort' => 'name']))
            ->assertOk()->assertExactJson(['results' => ['Alpha partner', 'Zulu partner']]);
    }

}
