<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Owns the signed-in user's notification list, counts, and read actions. */
class NotificationController extends Controller
{
    private const POST_TYPES = [
        'article' => [Article::class, 'article_id', 'articles.index', 'articles.show'],
        'activity' => [Activity::class, 'activity_id', 'activities.index', 'activities.show'],
        'achievement' => [Achievement::class, 'achievement_id', 'achievements.index', 'achievements.show'],
        'document' => [Document::class, 'document_id', 'documents.index', 'documents.show'],
        'liquidation' => [Liquidation::class, 'liquidation_id', 'liquidations.index', 'liquidations.show'],
    ];

    public function index(Request $request): View
    {
        $filter = $request->validate(['filter' => ['nullable', 'in:all,unread']])['filter'] ?? 'all';
        // SECURITY: The authenticated relation scopes pagination to this account only.
        $notifications = $request->user()->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(15)->withQueryString();
        return view('notifications.index', compact('notifications', 'filter'));
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        // SECURITY: Counts and payload rows are always scoped through this session's notification owner.
        $grouped = $user->notifications()->whereNull('read_at')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) AS notification_type, JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) AS post_type, COUNT(*) AS total")
            ->groupBy('notification_type', 'post_type')->get();
        $counts = $grouped->where('notification_type', 'published')->pluck('total', 'post_type');
        $latest = $user->notifications()->latest()->take(10)->get();
        $unreadCount = (int) $grouped->sum('total');

        return response()->json([
            'unread_count' => $unreadCount,
            'nav_counts' => $counts,
            'notifications' => $latest->map(fn ($notification) => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
                'data' => array_intersect_key((array) $notification->data, array_flip([
                    'type', 'post_type', 'post_id', 'title', 'actor_name', 'message', 'url', 'rejection_reason',
                ])),
            ])->values(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        // SECURITY: A foreign notification ID is indistinguishable from a missing one (404).
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $data = (array) $record->data;
        $type = $data['post_type'] ?? '';
        $mapping = self::POST_TYPES[$type] ?? null;
        if (! $mapping || ! isset($data['post_id'])) {
            return redirect()->route('notifications.index')->with('status', 'This notification link is no longer available.');
        }

        [$model, $key, $indexRoute, $showRoute] = $mapping;
        $post = $model::query()->where($key, $data['post_id'])->first();
        if (! $post || $post->content_status !== 'active') {
            return redirect()->route($indexRoute)->with('status', 'This post is no longer available.');
        }
        if (($data['type'] ?? '') === 'published' && $post->approval_status !== 'approved') {
            return redirect()->route($indexRoute)->with('status', 'This post is no longer available.');
        }
        if ($request->user()->isStudent() && ($post->approval_status !== 'approved' || $post->content_status !== 'active')) {
            return redirect()->route($indexRoute)->with('status', 'This post is no longer available.');
        }

        return redirect()->route($showRoute, $post->getKey());
    }

    public function readAll(Request $request): RedirectResponse
    {
        // SECURITY: Marking all read only updates rows attached to the current account.
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('status', 'All notifications were marked as read.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        // SECURITY: Deletion is scoped to the signed-in user's relation and returns 404 for foreign IDs.
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->delete();
        return back()->with('status', 'The notification was dismissed.');
    }

    public function avatar(Request $request, string $notification): RedirectResponse
    {
        // SECURITY: Avatar requests are tied to an owned notification and never accept a user ID.
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $data = (array) $record->data;
        $mapping = self::POST_TYPES[$data['post_type'] ?? ''] ?? null;
        if (! $mapping || ! isset($data['post_id'])) abort(404);
        [$model, $key] = $mapping;
        $post = $model::query()->where($key, $data['post_id'])->first();
        if (! $post) abort(404);

        // Submission notices show the posting officer; review notices show the adviser on the post.
        $actorId = ($data['type'] ?? '') === 'submitted' ? $post->created_by_user_id : $post->reviewed_by_user_id;
        $avatarUrl = $actorId ? User::query()->find($actorId)?->avatarUrl() : null;
        if (! $avatarUrl) abort(404);

        return redirect()->away($avatarUrl);
    }
}
