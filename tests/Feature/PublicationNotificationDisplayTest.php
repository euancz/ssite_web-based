<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PostPublished;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class PublicationNotificationDisplayTest extends TestCase
{
    use BuildsContentSchema;

    public function test_existing_publication_notices_show_content_type_and_the_authors_avatar(): void
    {
        $this->buildContentSchema();
        $author = User::factory()->create(['profile_picture' => 'https://example.com/author.jpg']);
        $reviewer = User::factory()->create(['role' => 'student', 'profile_completed_at' => now(), 'profile_picture' => 'https://example.com/reviewer.jpg']);
        $reader = User::factory()->create(['role' => 'student', 'profile_completed_at' => now()]);

        foreach (['articles' => 'article', 'activities' => 'activity', 'achievements' => 'achievement', 'documents' => 'document', 'liquidations' => 'liquidation'] as $table => $type) {
            $row = ['title' => 'Published content', 'created_by_user_id' => $author->id, 'reviewed_by_user_id' => $reviewer->id, 'approval_status' => 'approved'];
            if (in_array($type, ['document', 'liquidation'])) $row += ['file_path' => 'fixture.pdf', 'original_name' => 'fixture.pdf'];
            $id = DB::table($table)->insertGetId($row);
            $payload = (new PostPublished(['post_type' => $type, 'post_id' => $id]))->toArray($reader);
            $this->assertSame("A new {$type} has been published.", $payload['message']);
            $payload['message'] = 'A new post was published.';
            $notice = $reader->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => PostPublished::class, 'data' => $payload]);

            $this->actingAs($reader)->get('/notifications')->assertOk()->assertSee("A new {$type} has been published.");
            $this->getJson('/notifications/summary')->assertOk()->assertJsonFragment(['message' => "A new {$type} has been published."]);
            $this->get(route('notifications.avatar', $notice->id))->assertRedirect('https://example.com/author.jpg');
            $approval = $reader->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => \App\Notifications\PostApproved::class,
                'data' => array_merge($payload, ['type' => 'approved', 'message' => 'Your post was approved.']),
            ]);
            $this->get(route('notifications.avatar', $approval->id))->assertRedirect('https://example.com/reviewer.jpg');
            $this->get('/notifications')->assertOk()->assertSee('Your request was approved.')->assertDontSee('Your post was approved.');
            $this->getJson('/notifications/summary')->assertOk()->assertJsonFragment(['message' => 'Your request was approved.']);
            $this->actingAs($reviewer)->get(route('notifications.avatar', $notice->id))->assertNotFound();
        }
    }
}
