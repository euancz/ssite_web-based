<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Support\SearchText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class MainSiteFunctionalityTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        $this->actingAs(User::factory()->make(['id' => 99999, 'role' => 'student', 'profile_completed_at' => now()]));
    }

    private function postRecord(string $module, string $title, array $extra = []): int
    {
        $row = ['title' => $title, 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()];
        if (in_array($module, ['documents', 'liquidations'])) $row += ['file_path' => 'fixture.pdf', 'original_name' => 'fixture.pdf'];
        return DB::table($module)->insertGetId(array_replace($row, $extra));
    }

    public function test_discover_paginates_every_match_once_and_preserves_filters(): void
    {
        foreach (range(1, 22) as $i) $this->postRecord('articles', sprintf('Workshop %02d', $i));
        $this->postRecord('articles', 'Workshop private', ['approval_status' => 'pending']);
        $this->postRecord('articles', 'Workshop archived', ['content_status' => 'archived']);
        $seen = [];
        foreach ([1, 2, 3] as $page) {
            $response = $this->get('/discover?q=workshop&type=articles&period=week&sort=title&page='.$page)->assertOk();
            $stories = $response->viewData('stories');
            $this->assertSame(22, $stories->total());
            $seen = array_merge($seen, $stories->pluck('id')->all());
            $response->assertDontSee('Workshop private')->assertDontSee('Workshop archived');
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            foreach ($stories as $story) {
                $this->assertSame(1, $xpath->query('//main//h3[text()="'.$story->title.'"] | //main//h2/a[text()="'.$story->title.'"]')->length);
            }
            if ($page === 1) {
                parse_str(parse_url($stories->nextPageUrl(), PHP_URL_QUERY), $params);
                $this->assertSame(['q' => 'workshop', 'type' => 'articles', 'period' => 'week', 'sort' => 'title', 'page' => '2'], $params);
            }
        }
        $this->assertCount(22, array_unique($seen));
        $this->assertCount(22, $seen);
        $last = $this->get('/discover?type=articles&page=999')->assertOk()->viewData('stories');
        $this->assertSame(3, $last->currentPage());
    }

    public function test_discover_sorting_crosses_module_boundaries_and_handles_ties(): void
    {
        $this->postRecord('articles', 'Zulu old article', ['created_at' => now()->subDays(30)]);
        $this->postRecord('activities', 'Alpha new activity');
        $this->postRecord('achievements', 'Middle achievement', ['created_at' => now()->subDays(2)]);
        foreach (['recent', 'title', 'oldest'] as $sort) {
            $stories = $this->get('/discover?sort='.$sort)->assertOk()->viewData('stories');
            $expected = $sort === 'oldest'
                ? ['Zulu old article', 'Middle achievement', 'Alpha new activity']
                : ['Alpha new activity', 'Middle achievement', 'Zulu old article'];
            $this->assertSame($expected, $stories->pluck('title')->all());
        }
        foreach (['articles', 'activities', 'achievements'] as $module) $this->postRecord($module, 'Same title');
        $first = $this->get('/discover?sort=title')->viewData('stories')->map(fn ($p) => $p->getTable().':'.$p->id)->all();
        $second = $this->get('/discover?sort=title')->viewData('stories')->map(fn ($p) => $p->getTable().':'.$p->id)->all();
        $this->assertSame($first, $second);
    }

    public function test_search_handles_capitals_and_literal_wildcards_on_each_main_content_list(): void
    {
        foreach (['articles', 'activities', 'achievements', 'documents', 'liquidations'] as $module) {
            $this->postRecord($module, 'Workshop 100%_complete!');
            $this->postRecord($module, 'Workshop 100XYcomplete!');
            $path = $module === 'liquidations' ? '/transparency' : '/'.$module;
            $response = $this->get($path.'?q='.urlencode('workshop 100%_complete!'))->assertOk();
            $this->assertSame(['Workshop 100%_complete!'], $response->viewData($module)->pluck('title')->all());
        }
        $this->assertSame(3, $this->get('/discover?q='.urlencode('workshop 100%_complete!'))->assertOk()->viewData('stories')->total());
        $this->assertSame(5, $this->get('/search?q='.urlencode('workshop 100%_complete!'))->assertOk()->viewData('results')->total());
        $this->getJson('/search/suggest?q='.urlencode('workshop 100%_complete!'))->assertOk()->assertJsonCount(5, 'results');
        // Compile against PostgreSQL without connecting: SQLite alone cannot catch the original LIKE regression.
        $query = Article::on('pgsql');
        SearchText::contains($query, 'title', 'workshop 100%_complete!');
        $this->assertStringContainsString('"title" ILIKE ? ESCAPE', $query->toSql());
        $this->assertSame(['%workshop 100!%!_complete!!%'], $query->getBindings());
    }

    public function test_lists_preserve_unread_notices_and_public_detail_reads_only_this_users_matching_post(): void
    {
        foreach (['articles' => 'article', 'activities' => 'activity', 'achievements' => 'achievement', 'documents' => 'document', 'liquidations' => 'liquidation'] as $module => $type) {
            $postId = $this->postRecord($module, 'Visible post');
            $matching = $this->notice($type, $postId);
            $unseen = $this->notice($type, $postId + 1);
            $foreign = $this->notice($type, $postId, 88888);
            $review = $this->notice($type, $postId, 99999, 'approved');
            $path = $module === 'liquidations' ? '/transparency' : '/'.$module;
            $this->get($path.'?q=nonexistent')->assertOk();
            $this->get($path)->assertOk();
            foreach ([$matching, $unseen, $foreign, $review] as $id) $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));
            $detail = $module === 'liquidations' ? '/liquidation' : '/'.$module;
            $this->get($detail.'/'.$postId)->assertOk();
            $this->assertNotNull(DB::table('notifications')->where('id', $matching)->value('read_at'));
            foreach ([$unseen, $foreign, $review] as $id) $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));
            $private = $this->postRecord($module, 'Private post', ['approval_status' => 'pending']);
            $privateNotice = $this->notice($type, $private);
            $this->get($detail.'/'.$private)->assertNotFound();
            $this->assertNull(DB::table('notifications')->where('id', $privateNotice)->value('read_at'));
        }
        $this->post('/notifications/read-all')->assertRedirect();
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', 99999)->whereNull('read_at')->count());
        $this->assertSame(5, DB::table('notifications')->where('notifiable_id', 88888)->whereNull('read_at')->count());
    }

    private function notice(string $type, int $post, int $user = 99999, string $event = 'published'): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $id, 'type' => 'publication', 'notifiable_type' => User::class, 'notifiable_id' => $user,
            'data' => json_encode(['type' => $event, 'post_type' => $type, 'post_id' => $post]), 'created_at' => now(), 'updated_at' => now()]);
        return $id;
    }
}
