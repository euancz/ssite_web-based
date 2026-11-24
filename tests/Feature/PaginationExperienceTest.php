<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class PaginationExperienceTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]));
    }

    private function records(string $table, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $row = ['title' => sprintf('Workshop %03d', $i), 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()];
            if (in_array($table, ['documents', 'liquidations'])) {
                $row += ['file_path' => $table.'/fixture.pdf', 'original_name' => 'fixture.pdf'];
            }
            if ($table === 'documents') $row += ['category' => 'Minutes', 'semester' => 1];
            DB::table($table)->insert($row);
        }
    }

    public function test_all_public_lists_have_stable_pages_and_recover_from_stale_page_numbers(): void
    {
        foreach (['articles', 'activities', 'achievements', 'documents', 'liquidations'] as $table) {
            $this->records($table, 11);
            $path = $table === 'liquidations' ? '/transparency' : '/'.$table;
            $ids = [];
            foreach ([1, 2, 3] as $page) {
                $response = $this->get($path.'?sort=title&page='.$page)->assertOk();
                $paginator = $response->viewData($table);
                $this->assertSame(11, $paginator->total());
                $this->assertSame($page, $paginator->currentPage());
                $ids = array_merge($ids, $paginator->pluck('id')->all());
            }
            $this->assertCount(11, array_unique($ids));
            $last = $this->get($path.'?sort=title&page=999')->assertOk()->viewData($table);
            $this->assertSame(3, $last->currentPage());
            $this->assertSame(11, $last->firstItem());
            $this->assertFalse($last->hasMorePages());
            $this->assertSame([end($ids)], $last->pluck('id')->all());
        }
    }

    public function test_filtered_counts_and_links_exclude_private_records_and_retain_filters(): void
    {
        $this->records('documents', 7);
        DB::table('documents')->insert(['title' => 'Workshop private', 'category' => 'Minutes', 'semester' => 1, 'approval_status' => 'pending', 'file_path' => 'documents/private.pdf', 'original_name' => 'private.pdf']);
        $response = $this->get('/documents?q=Workshop&semester=1&category=Minutes&sort=title&page=2')->assertOk();
        $paginator = $response->viewData('documents');
        $this->assertSame(7, $paginator->total());
        $this->assertSame(6, $paginator->firstItem());
        $this->assertSame(7, $paginator->lastItem());
        parse_str(parse_url($paginator->previousPageUrl(), PHP_URL_QUERY), $parameters);
        foreach (['q' => 'Workshop', 'semester' => '1', 'category' => 'Minutes', 'sort' => 'title', 'page' => '1'] as $key => $value) {
            $this->assertSame($value, $parameters[$key]);
        }
        $response->assertDontSee('Workshop private')->assertSee('aria-label="Page 2, current page"', false);
    }

    public function test_empty_and_single_page_lists_show_accurate_counts_without_navigation(): void
    {
        $this->get('/documents?page=999')->assertOk()->assertSee('No results')->assertDontSee('aria-label="Results pagination"', false);
        $this->records('documents', 1);
        $this->get('/documents')->assertOk()->assertSee('Showing')->assertSee('of <strong>1</strong> result', false)->assertDontSee('aria-label="Results pagination"', false);
    }

    public function test_invalid_pages_return_the_first_page_and_global_search_clamps_to_available_results(): void
    {
        $this->records('articles', 12);
        foreach (['-2', '0', 'bad', '1.5', '99999999999999999999999999999999'] as $page) {
            $this->assertSame(1, $this->get('/articles?page='.$page)->assertOk()->viewData('articles')->currentPage());
        }
        $this->assertSame(1, $this->get('/articles?page[]=2')->assertOk()->viewData('articles')->currentPage());
        $results = $this->get('/search?q=workshop&page=999')->assertOk()->assertSee('Results pagination')->viewData('results');
        $this->assertSame(2, $results->currentPage());
        $this->assertSame(12, $results->total());
        $this->assertCount(2, $results->items());
    }

    public function test_every_paginated_screen_loads_the_shared_footer_and_versioned_styles(): void
    {
        (require database_path('migrations/2026_11_14_000002_create_sponsors_table.php'))->up();
        (require database_path('migrations/2026_10_27_000006_create_content_versions_table.php'))->up();
        $owner = User::forceCreate(['name' => 'Pagination President', 'email' => 'pagination-president@mcc.edu.ph', 'role' => 'officer', 'officer_position' => 'President', 'profile_completed_at' => now()]);
        $post = DB::table('articles')->insertGetId(['title' => 'Pagination story', 'created_by_user_id' => $owner->id, 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($owner);
        $screens = [
            ['notifications.index', []], ['workspace.sponsors.index', []], ['workspace.drafts.index', []],
            ['workspace.content-activity.index', []], ['adviser.reviews.index', []],
            ['workspace.content-versions.index', ['module' => 'articles', 'post' => $post]],
        ];
        foreach (['articles', 'activities', 'achievements', 'documents', 'liquidations'] as $module) {
            $screens[] = ['workspace.posts.'.$module.'.index', []];
        }
        foreach ($screens as [$name, $parameters]) {
            $this->get(route($name, $parameters + ['page' => 999]))->assertOk()
                ->assertSee('class="ssite-pagination"', false)->assertSee('data-pagination-page="1"', false)
                ->assertSee('css/pagination.css?v=', false);
        }
        $manager = User::forceCreate(['name' => 'Pagination Adviser', 'email' => 'pagination-adviser@mcc.edu.ph', 'role' => 'adviser', 'profile_completed_at' => now()]);
        $this->actingAs($manager)->get(route('adviser.users.index', ['role' => 'officer', 'search' => 'Pagination President', 'page' => 999]))->assertOk()
            ->assertSee('class="workspace-list-toolbar"', false)
            ->assertSee('name="search" maxlength="100" value="Pagination President"', false)
            ->assertSee('value="officer" selected', false)->assertSee('Pagination President')
            ->assertSee('class="ssite-pagination"', false)->assertSee('css/pagination.css?v=', false)
            ->assertDontSee('aria-label="User pagination"', false);
    }

    public function test_default_laravel_links_use_the_shared_design(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 5), 12, 5, 1, ['path' => '/notifications']);
        $this->assertStringContainsString('class="ssite-pagination"', $paginator->links()->toHtml());
    }

    public function test_notification_pagination_remains_owner_scoped_and_preserves_unread_filter(): void
    {
        $owner = User::forceCreate(['name' => 'Notice owner', 'email' => 'notice-owner@mcc.edu.ph', 'role' => 'student', 'profile_completed_at' => now()]);
        $other = User::forceCreate(['name' => 'Other owner', 'email' => 'notice-other@mcc.edu.ph', 'role' => 'student', 'profile_completed_at' => now()]);
        foreach ([$owner->id => 16, $other->id => 3] as $id => $count) {
            for ($i = 0; $i < $count; $i++) {
                DB::table('notifications')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'publication', 'notifiable_type' => User::class, 'notifiable_id' => $id,
                    'data' => json_encode(['title' => 'Notice '.$i, 'type' => 'published']), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $response = $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'unread', 'page' => 999]))->assertOk();
        $paginator = $response->viewData('notifications');
        $this->assertSame(16, $paginator->total());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertCount(1, $paginator->items());
        $this->assertSame($owner->id, $paginator->first()->notifiable_id);
        $this->assertStringContainsString('filter=unread', $paginator->previousPageUrl());
    }
    public function test_large_page_sets_include_first_last_current_and_noninteractive_gaps(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 5), 250, 5, 25, ['path' => '/documents']);
        $html = $paginator->onEachSide(1)->links('vendor.pagination.ssite')->toHtml();
        $this->assertStringContainsString('Go to page 1', $html);
        $this->assertStringContainsString('Go to page 50', $html);
        $this->assertStringContainsString('Page 25, current page', $html);
        $this->assertStringContainsString('ssite-page-ellipsis', $html);
        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('rel="next"', $html);
    }
}
