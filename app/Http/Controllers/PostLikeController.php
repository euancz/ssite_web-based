<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\PostLike;
use App\Support\PostEngagement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Toggles likes only for public posts after checking the feature flag and backing table. */
class PostLikeController extends Controller
{
    use AuthorizesRequests;

    private const TARGETS = [
        'article' => [Article::class, 'article_id', 'articles.show', 'article'],
        'activity' => [Activity::class, 'activity_id', 'activities.show', 'activity'],
        'achievement' => [Achievement::class, 'achievement_id', 'achievements.show', 'achievement'],
        'document' => [Document::class, 'document_id', 'documents.show', 'document'],
        'liquidation' => [Liquidation::class, 'liquidation_id', 'liquidations.show', 'liquidation'],
    ];

    /** Store the requested like state so duplicate submissions from open tabs stay idempotent. */
    public function toggle(Request $request, string $type, int $post, PostEngagement $engagement): RedirectResponse
    {
        abort_unless(config('school.enable_likes', false) && $engagement->likesAvailable(), 404);
        abort_unless(isset(self::TARGETS[$type]), 404);
        [$modelClass, $keyName, $showRoute, $routeParameter] = self::TARGETS[$type];
        $showUrl = route($showRoute, [$routeParameter => $post]);
        $user = $request->user();

        // Store the public detail page as the intended destination because login cannot replay a POST.
        if (! $user) {
            $request->session()->put('url.intended', $showUrl);
            return redirect('/')->with('error', 'Sign in first to like this post.');
        }
        if (! $user->hasCompletedProfile()
            && !(($user->isAdviser() || $user->isSuperadmin()) && ! config('school.require_profile_for_all_roles', true))) {
            $request->session()->put('url.intended', $showUrl);
            return redirect()->route('profile.complete');
        }

        $desiredLike = $request->input('liked');
        abort_unless(is_string($desiredLike) && in_array($desiredLike, ['0', '1'], true), 422);
        $desiredLike = $desiredLike === '1';

        // SECURITY: Locking the public post serializes two tabs before they check or create its unique user like.
        DB::transaction(function () use ($modelClass, $keyName, $post, $type, $user, $desiredLike): void {
            $lockedPost = $modelClass::query()->where($keyName, $post)->lockForUpdate()->firstOrFail();
            abort_unless($lockedPost->isApproved() && $lockedPost->content_status === 'active', 404);
            $this->authorize('view', $lockedPost);

            $like = PostLike::query()->where('post_type', $type)
                ->where('post_id', $lockedPost->getKey())
                ->where('user_id', $user->user_id)
                ->lockForUpdate()
                ->first();

            if ($desiredLike && ! $like) {
                $like = new PostLike();
                $like->post_type = $type;
                $like->post_id = $lockedPost->getKey();
                $like->user_id = $user->user_id;
                $like->created_at = now();
                $like->save();
            } elseif (! $desiredLike && $like) {
                $like->delete();
            }
        });

        return back()->with('status', $desiredLike ? 'You liked this post.' : 'You removed your like.');
    }
}
