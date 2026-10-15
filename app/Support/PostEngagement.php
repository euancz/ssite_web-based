<?php

namespace App\Support;

use App\Models\PostLike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Keeps optional post engagement inert until its flag and owner-created SQL schema exist. */
class PostEngagement
{
    private array $viewColumns = [];
    private ?bool $likesTable = null;

    public function viewCountsAvailable(Model $post): bool
    {
        if (! config('school.enable_view_counts', false)) return false;

        $table = $post->getTable();
        return $this->viewColumns[$table] ??= Schema::hasColumn($table, 'view_count');
    }

    public function likesAvailable(): bool
    {
        if (! config('school.enable_likes', false)) return false;

        return $this->likesTable ??= Schema::hasTable('post_likes');
    }

    /** Count a public view once per browser session without changing the post timestamp. */
    public function recordView(Model $post, Request $request): bool
    {
        if (! $this->viewCountsAvailable($post)
            || $post->approval_status !== 'approved'
            || $post->content_status !== 'active') {
            return false;
        }

        $user = $request->user();
        if ($user && (int) $post->created_by_user_id === (int) $user->user_id) return false;

        $agent = (string) $request->userAgent();
        $botFragments = config('school.view_bot_user_agents', []);
        if ($agent !== '' && $botFragments && Str::contains($agent, $botFragments, true)) return false;

        $sessionKey = 'viewed_post_' . sha1($post->getTable() . ':' . $post->getKey());
        $now = now()->timestamp;
        $lastViewed = $request->session()->get($sessionKey);
        $dedupeSeconds = max(0, (int) config('school.view_dedupe_hours', 12)) * 3600;
        if (is_numeric($lastViewed) && ($now - (int) $lastViewed) < $dedupeSeconds) return false;

        // SECURITY: The query builder increment avoids Eloquent's automatic updated_at write.
        $changed = DB::table($post->getTable())->where($post->getKeyName(), $post->getKey())->increment('view_count');
        if (! $changed) return false;

        $request->session()->put($sessionKey, $now);
        $post->view_count = (int) $post->view_count + 1;

        return true;
    }

    /** Remove orphan likes on permanent deletion whenever the optional table exists. */
    public function deleteLikesForPost(Model $post): void
    {
        if (! $this->postLikesTableExists()) return;

        PostLike::query()->where('post_type', $post::POST_TYPE)->where('post_id', $post->getKey())->delete();
    }

    private function postLikesTableExists(): bool
    {
        return $this->likesTable ??= Schema::hasTable('post_likes');
    }
}
