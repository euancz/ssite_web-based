<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ContentDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class SearchCategoriesTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        (require database_path('migrations/2026_10_27_000007_add_category_to_articles_table.php'))->up();
        (require database_path('migrations/2026_11_15_000001_add_search_categories_and_report_semester.php'))->up();
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]));
    }

    public function test_requested_categories_are_available_before_posts_exist(): void
    {
        foreach ([
            '/documents' => ['Progdes &amp; SaS', 'Memo &amp; Resolution', 'Letters'],
            '/articles' => ['Organization News', 'Student Features', 'Educational Articles', 'Event Highlights'],
            '/achievements' => ['Organizational Awards', 'Competition Awards', 'Student Awards'],
            '/activities' => ['Seminars and Webinars', 'Workshops', 'Competition', 'Community Outreach'],
        ] as $path => $labels) {
            $response = $this->get($path)->assertOk();
            foreach ($labels as $label) $response->assertSee($label, false);
        }
        $this->get('/activities?category=Community%20Outreach')->assertOk()->assertViewHas('category', 'Community Outreach');
    }

    public function test_activity_category_combines_with_timeframe_search_and_pagination(): void
    {
        foreach (range(1, 7) as $i) {
            DB::table('activities')->insert(['title' => 'Training '.$i, 'category' => 'Workshops', 'description' => 'Learn together',
                'activity_date' => today()->addDay(), 'approval_status' => $i === 7 ? 'pending' : 'approved', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('activities')->insert(['title' => 'Past training', 'category' => 'Workshops', 'activity_date' => today()->subDay(), 'approval_status' => 'approved']);
        $response = $this->get('/activities?category=Workshops&q=training&timeframe=upcoming')->assertOk();
        $this->assertSame(6, $response->viewData('activities')->total());
        $this->assertStringContainsString('category=Workshops', $response->viewData('activities')->nextPageUrl());
        $response->assertDontSee('Training 7')->assertDontSee('Past training');
        $this->get('/activities?category[]=Workshops')->assertOk()->assertViewHas('category', '');
    }

    public function test_discover_categories_only_appear_for_the_selected_content_type(): void
    {
        $this->get('/discover')->assertOk()->assertDontSee('id="discover-category"', false)
            ->assertViewHas('categoryGroups', []);
        foreach (['articles' => ['Organization News', 'Workshops'],
            'activities' => ['Workshops', 'Student Awards'],
            'achievements' => ['Student Awards', 'Organization News']] as $type => [$included, $excluded]) {
            $response = $this->get('/discover?type='.$type)->assertOk()->assertSee('id="discover-category"', false);
            $options = array_merge(...array_values($response->viewData('categoryGroups')));
            $this->assertArrayHasKey($included, $options);
            $this->assertArrayNotHasKey($excluded, $options);
        }
    }

    public function test_discover_clears_categories_from_another_type_or_all_content(): void
    {
        DB::table('articles')->insert(['title' => 'Student spotlight', 'category' => 'Student Features', 'approval_status' => 'approved', 'created_at' => now()]);
        DB::table('activities')->insert(['title' => 'Training day', 'category' => 'Workshops', 'approval_status' => 'approved', 'created_at' => now()]);
        $all = $this->get('/discover?type=all&category=Student%20Features')->assertOk();
        $this->assertSame('', $all->viewData('filters')['category']);
        $this->assertSame(2, $all->viewData('stories')->total());
        $switched = $this->get('/discover?type=activities&category=Student%20Features')->assertOk();
        $this->assertSame('', $switched->viewData('filters')['category']);
        $this->assertSame(['Training day'], $switched->viewData('activities')->pluck('title')->all());
    }

    public function test_category_keyword_search_and_discover_exclude_unpublished_posts(): void
    {
        foreach (['articles', 'activities', 'achievements'] as $table) {
            DB::table($table)->insert(['title' => 'Visible '.$table, 'category' => 'Student Features', 'approval_status' => 'approved', 'created_at' => now()]);
            DB::table($table)->insert(['title' => 'Private '.$table, 'category' => 'Student Features', 'approval_status' => 'pending', 'created_at' => now()]);
        }
        $response = $this->get('/search?q=Student%20Features')->assertOk();
        $this->assertCount(3, $response->viewData('results')->items());
        $response->assertDontSee('Private articles')->assertDontSee('Private activities');
        $discover = $this->get('/discover?type=articles&category=Student%20Features')->assertOk();
        $this->assertSame(['Visible articles'], $discover->viewData('articles')->pluck('title')->all());
        $this->get('/articles?q=Student%20Features')->assertOk()->assertSee('Visible articles')->assertDontSee('Private articles');
    }

    public function test_transparency_event_and_semester_filters_preserve_visibility(): void
    {
        foreach ([['Workshop', 1, 'approved'], ['Workshop', 2, 'approved'], ['Private event', 1, 'pending'], ['Assembly', 1, 'approved']] as [$title, $semester, $status]) {
            DB::table('liquidations')->insert(['title' => $title, 'semester' => $semester, 'approval_status' => $status,
                'file_path' => 'reports/test.pdf', 'original_name' => 'test.pdf', 'created_at' => now()]);
        }
        $response = $this->get('/transparency?event=Workshop&semester=1')->assertOk();
        $this->assertSame(1, $response->viewData('liquidations')->total());
        $this->assertEquals(1, $response->viewData('liquidations')->first()->semester);
        $response->assertSee('Academic semester')->assertDontSee('Private event');
        $this->get('/transparency?event[]=bad&semester[]=1')->assertOk()->assertViewHas('semester', '')->assertViewHas('event', '');
    }

    public function test_activity_category_survives_draft_submission_and_edit(): void
    {
        $author = User::forceCreate(['name' => 'President', 'email' => 'categories@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => 'President', 'profile_completed_at' => now()]);
        $this->actingAs($author);
        $this->get(route('activities.create'))->assertOk()->assertSee('Community Outreach');
        $this->post(route('workspace.drafts.save', 'activities'), ['title' => 'Community day', 'category' => 'Community Outreach'])->assertRedirect()->assertSessionHasNoErrors();
        $draft = ContentDraft::firstOrFail();
        $this->assertSame('Community Outreach', $draft->payload['category']);
        $this->get(route('workspace.drafts.edit', $draft))->assertOk()->assertSee('value="Community Outreach" selected', false);
        $this->post(route('activities.store'), ['title' => 'Community day', 'description' => 'Helping neighbors', 'category' => 'Community Outreach',
            'draft_id' => $draft->getKey(), 'draft_version' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $post = Activity::firstOrFail();
        $this->assertSame('Community Outreach', $post->category);
        $this->patch(route('activities.update', $post), ['title' => $post->title, 'description' => $post->description, 'category' => 'Workshops'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Workshops', $post->fresh()->category);
    }
}
