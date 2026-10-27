<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\ContentReview;
use App\Services\ContentReviewHistory;
use App\Support\ContentModules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class ContentReviewHistoryTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function enable(): void
    {
        (require database_path('migrations/2026_10_27_000005_create_content_review_events_table.php'))->up();
    }

    private function actor(string $position = 'President'): User
    {
        $user = new User;
        $user->forceFill(['name' => $position, 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer',
            'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    private function record(User $owner, string $model = Article::class): Model
    {
        $post = new $model;
        $values = ['title' => 'Original title', 'created_by_user_id' => $owner->getKey(),
            'approval_status' => 'pending', 'content_status' => 'active'];
        $values[$model === Article::class ? 'content' : 'description'] = 'Body';
        if (in_array($model::POST_TYPE, ['document', 'liquidation'], true)) {
            $values += ['file_path' => 'private.pdf', 'original_name' => 'report.pdf'];
        }
        $post->forceFill($values)->save();

        return $post->fresh();
    }

    public function test_migration_is_additive_and_reviews_work_before_it_runs(): void
    {
        $reviewer = $this->actor();
        $post = $this->record($reviewer);
        $this->assertTrue(app(ContentReview::class)->decide($post, $reviewer, true));
        $this->actingAs($reviewer)->get(route('workspace.posts.articles.show', $post))->assertOk()->assertDontSee('Review history');
        $before = DB::table('articles')->first();
        $this->enable();
        $this->assertEquals($before, DB::table('articles')->first());
        $this->assertSame(0, DB::table('content_review_events')->count());
        $this->assertTrue(collect(Schema::getIndexes('content_review_events'))->contains(fn ($index) => $index['name'] === 'content_review_events_post_index'));
        $this->get(route('workspace.posts.articles.show', $post))->assertOk()->assertSee('No decisions have been recorded');
    }

    public function test_all_five_modules_record_decisions_and_repeated_reviews_add_no_event(): void
    {
        $this->enable();
        $reviewer = $this->actor();
        foreach (ContentModules::all() as $module) {
            $post = $this->record($reviewer, $module['model']);
            $this->assertTrue(app(ContentReview::class)->decide($post, $reviewer, true));
            $this->assertFalse(app(ContentReview::class)->decide($post->fresh(), $reviewer, true));
            $events = app(ContentReviewHistory::class)->recentFor($post, $reviewer);
            $this->assertCount(1, $events);
            $this->assertSame($post::POST_TYPE, $events->first()->post_type);
            $this->assertSame('approved', $events->first()->decision);
            $this->assertNull($events->first()->reason);
        }
        $this->assertSame(5, DB::table('content_review_events')->count());
    }

    public function test_rejection_resubmission_and_approval_keep_original_snapshots(): void
    {
        $this->enable();
        $reviewer = $this->actor();
        $post = $this->record($this->actor('Multimedia'));
        app(ContentReview::class)->decide($post, $reviewer, false, '<script>Fix sources</script>');
        $reviewer->name = 'Renamed reviewer';
        $reviewer->save();
        $post->forceFill(['title' => 'Revised title', 'approval_status' => 'pending'])->save();
        app(ContentReview::class)->decide($post->fresh(), $reviewer, true);
        $events = app(ContentReviewHistory::class)->recentFor($post, $reviewer);
        $this->assertCount(2, $events);
        $this->assertSame('Revised title', $events[0]->post_title);
        $this->assertSame('Renamed reviewer', $events[0]->reviewer_name);
        $this->assertSame('Original title', $events[1]->post_title);
        $this->assertSame('President', $events[1]->reviewer_name);
        $this->assertSame('<script>Fix sources</script>', $events[1]->reason);
        $this->actingAs($reviewer)->get(route('workspace.posts.articles.show', $post))->assertOk()
            ->assertSee('Original title')->assertSee('&lt;script&gt;Fix sources&lt;/script&gt;', false)->assertDontSee('<script>Fix sources</script>', false);
        $post->forceFill(['content_status' => 'archived'])->save();
        $this->assertCount(2, app(ContentReviewHistory::class)->recentFor($post, $reviewer));
    }

    public function test_history_is_private_to_current_editors_even_for_published_content(): void
    {
        $this->enable();
        $owner = $this->actor('Multimedia');
        $other = $this->actor('Multimedia');
        $reviewer = $this->actor();
        $post = $this->record($owner);
        app(ContentReview::class)->decide($post, $reviewer, true);
        $this->get(route('articles.show', $post))->assertOk()->assertDontSee('Review history');
        $this->actingAs($owner)->get(route('workspace.posts.articles.show', $post))->assertOk()->assertSee('Review history');
        $this->actingAs($other)->get(route('workspace.posts.articles.show', $post))->assertOk()->assertDontSee('Review history');
        try {
            app(ContentReviewHistory::class)->recentFor($post, $other);
            $this->fail('Another author must not read history.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
        $owner->officer_position = 'Secretary';
        $owner->save();
        $this->expectException(AuthorizationException::class);
        app(ContentReviewHistory::class)->recentFor($post, $owner);
    }

    public function test_history_insert_failure_rolls_back_decision_and_notifications(): void
    {
        $this->enable();
        $post = $this->record($this->actor('Multimedia'));
        $reviewer = $this->actor();
        DB::statement("CREATE TRIGGER reject_history BEFORE INSERT ON content_review_events BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            app(ContentReview::class)->decide($post, $reviewer, true);
            $this->fail('A history insertion failure must roll back the decision.');
        } catch (QueryException) {
            $this->assertSame('pending', $post->fresh()->approval_status);
            $this->assertNull($post->fresh()->reviewed_at);
            $this->assertSame(0, DB::table('content_review_events')->count());
            $this->assertSame(0, DB::table('notifications')->count());
        }
    }

    public function test_stale_or_unauthorized_decisions_never_add_history(): void
    {
        $this->enable();
        $owner = $this->actor('Multimedia');
        $post = $this->record($owner);
        Article::whereKey($post->getKey())->update(['content' => 'New version']);
        $this->assertFalse(app(ContentReview::class)->decide($post, $this->actor(), true));
        $this->actingAs($owner)->post(route('articles.approve', $post))->assertForbidden();
        $this->assertSame(0, DB::table('content_review_events')->count());
    }

    public function test_history_is_bounded_deterministic_and_survives_source_deletion(): void
    {
        $this->enable();
        $reviewer = $this->actor();
        $post = $this->record($reviewer);
        for ($i = 0; $i < 21; $i++) {
            $post->refresh()->forceFill(['approval_status' => 'pending', 'title' => 'Revision '.$i])->save();
            app(ContentReview::class)->decide($post, $reviewer, true);
        }
        $events = app(ContentReviewHistory::class)->recentFor($post, $reviewer);
        $this->assertCount(20, $events);
        $this->assertSame('Revision 20', $events->first()->post_title);
        $this->assertSame('Revision 1', $events->last()->post_title);
        $post->delete();
        // Snapshot storage has no cascading foreign keys; this is not a history deletion endpoint.
        $this->assertSame(21, DB::table('content_review_events')->count());
    }
}
