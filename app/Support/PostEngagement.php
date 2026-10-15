<?php

namespace App\Support;

use App\Models\PostLike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Records post engagement while checking the feature flags and required SQL schema. */
class PostEngagement
{
    private array $viewColumns = [];
    private ?bool $likesTable = null;
    private ?bool $viewsTable = null;

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

    public function likeCountsAvailable(Model $post): bool
    {
        return config('school.enable_likes', false)
            && Schema::hasColumn($post->getTable(), 'like_count');
    }

    /** Load like-row state for a public detail page when both backing schemas are available. */
    public function loadLikeState(Model $post, Request $request): void
    {
        if (! $this->likesAvailable() || ! $this->likeCountsAvailable($post)
            || $post->approval_status !== 'approved' || $post->content_status !== 'active') {
            return;
        }

        $post->loadCount('likes');
        if ($user = $request->user()) {
            $post->loadCount(['likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $user->user_id)]);
        }
    }

    /** Count a public view once per signed-in user, or once per guest session. */
    public function recordView(Model $post, Request $request): bool
    {
        if (! $this->viewCountsAvailable($post)
            || $post->approval_status !== 'approved'
            || $post->content_status !== 'active') {
            return false;
        }

        $user = $request->user();
        $sessionKey = 'viewed_post_' . sha1($post->getTable() . ':' . $post->getKey());

        if ($user) {
            if (! $this->postViewsTableExists()) return false;

            // SECURITY: The unique key makes first-view tracking safe across sessions and concurrent requests.
            return DB::transaction(function () use ($post, $user): bool {
                $inserted = DB::table('post_views')->insertOrIgnore([
                    'post_type' => $post::POST_TYPE,
                    'post_id' => $post->getKey(),
                    'user_id' => $user->user_id,
                    'created_at' => now(),
                ]);
                if (! $inserted) return false;

                DB::table($post->getTable())->where($post->getKeyName(), $post->getKey())->increment('view_count');
                $post->view_count = (int) $post->view_count + 1;

                return true;
            });
        }

        $agent = (string) $request->userAgent();
        $botFragments = config('school.view_bot_user_agents', []);
        if ($agent !== '' && $botFragments && Str::contains($agent, $botFragments, true)) return false;

        $lastViewed = $request->session()->get($sessionKey);
        $now = now()->timestamp;
        $dedupeSeconds = max(0, (int) config('school.view_dedupe_hours', 12)) * 3600;
        if (is_numeric($lastViewed) && ($now - (int) $lastViewed) < $dedupeSeconds) return false;

        // SECURITY: The query builder increment avoids Eloquent's automatic updated_at write.
        $changed = DB::table($post->getTable())->where($post->getKeyName(), $post->getKey())->increment('view_count');
        if (! $changed) return false;

        $request->session()->put($sessionKey, $now);
        $post->view_count = (int) $post->view_count + 1;

        return true;
    }

    private function postViewsTableExists(): bool
    {
        return $this->viewsTable ??= Schema::hasTable('post_views');
    }

    /** Remove related like and view records on permanent deletion when their tables exist. */
    public function deleteLikesForPost(Model $post): void
    {
        if ($this->postLikesTableExists()) {
            PostLike::query()->where('post_type', $post::POST_TYPE)->where('post_id', $post->getKey())->delete();
        }
        if ($this->postViewsTableExists()) {
            DB::table('post_views')->where('post_type', $post::POST_TYPE)->where('post_id', $post->getKey())->delete();
        }
    }

    private function postLikesTableExists(): bool
    {
        return $this->likesTable ??= Schema::hasTable('post_likes');
    }
}
