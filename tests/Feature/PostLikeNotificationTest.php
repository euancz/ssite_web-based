<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PostLiked;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class PostLikeNotificationTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
        foreach (['articles', 'activities', 'achievements', 'documents', 'liquidations'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->unsignedInteger('like_count')->default(0));
        }
        (require database_path('migrations/2026_10_15_000000_create_post_likes_table.php'))->up();
        config(['school.enable_likes' => true]);
    }

    public function test_likes_notify_only_the_author_once_and_open_the_public_post(): void
    {
        $author = User::factory()->create(['role' => 'officer', 'officer_position' => 'President', 'profile_completed_at' => now()]);
        $actor = User::factory()->create(['name' => 'Alex Student', 'role' => 'student', 'profile_completed_at' => now()]);
        foreach (['articles' => 'article', 'activities' => 'activity', 'achievements' => 'achievement', 'documents' => 'document', 'liquidations' => 'liquidation'] as $table => $type) {
            $row = ['title' => 'A public post', 'created_by_user_id' => $author->id, 'approval_status' => 'approved'];
            if (in_array($type, ['document', 'liquidation'])) $row += ['file_path' => 'fixture.pdf', 'original_name' => 'fixture.pdf'];
            $id = DB::table($table)->insertGetId($row);
            $endpoint = '/posts/'.$type.'/'.$id.'/like';
            $before = $author->notifications()->count();
            $this->actingAs($actor)->post($endpoint, ['liked' => '1'])->assertRedirect();
            $this->post($endpoint, ['liked' => '1'])->assertRedirect();
            $this->assertSame($before + 1, $author->notifications()->count());
            $notice = $author->notifications()->where('type', PostLiked::class)->get()->first(fn ($n) => $n->data['post_type'] === $type);
            $this->assertSame('Alex Student liked your post.', $notice->data['message']);
            $this->assertSame($actor->id, $notice->data['actor_id']);
            $this->assertSame($id, $notice->data['post_id']);
            $this->assertSame(0, $actor->notifications()->count());
            $this->assertSame(1, DB::table($table)->where('id', $id)->value('like_count'));
            $this->post($endpoint, ['liked' => '0'])->assertRedirect();
            $this->assertSame($before + 1, $author->notifications()->count());
            $this->actingAs($author)->post($endpoint, ['liked' => '1'])->assertRedirect();
            $this->assertSame($before + 1, $author->notifications()->count());
            $this->get('/notifications')->assertOk()->assertSee('Alex Student liked your post.');
            $this->getJson('/notifications/summary')->assertOk()->assertJsonFragment(['message' => 'Alex Student liked your post.']);
            $this->post('/notifications/'.$notice->id.'/read')->assertRedirect(route(match ($type) {
                'article' => 'articles.show', 'activity' => 'activities.show', 'achievement' => 'achievements.show',
                'document' => 'documents.show', 'liquidation' => 'liquidations.show',
            }, $id));
            $this->assertNotNull($notice->fresh()->read_at);
        }
    }

    public function test_private_posts_and_posts_without_authors_do_not_send_notifications(): void
    {
        $actor = User::factory()->create(['role' => 'student', 'profile_completed_at' => now()]);
        $this->actingAs($actor);
        foreach (['pending', 'approved'] as $status) {
            $id = DB::table('articles')->insertGetId(['title' => 'A post', 'approval_status' => $status]);
            $response = $this->post('/posts/article/'.$id.'/like', ['liked' => '1']);
            if ($status === 'pending') $response->assertNotFound();
            else $response->assertRedirect();
        }
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('post_likes', 1);
    }
}
