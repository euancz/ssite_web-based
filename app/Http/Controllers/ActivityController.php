<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\ActivityRequest;
use App\Models\Activity;
use App\Services\ContentReview;
use App\Services\PostFiles;
use App\Support\ContentListing;
use App\Support\PostEngagement;
use App\Support\PostNotificationRecipients;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/* Officer submissions follow position capabilities and start pending. The President reviews;
 * public queries require approved and active state, while archive and restore preserve review state.
 */
/** Lists and manages activities while enforcing position capabilities and review state server-side. */
class ActivityController extends Controller
{
    use AuthorizesRequests;

    /** Show each role only the activity tabs and records available to that role. */
    public function index(Request $request, PostEngagement $engagement): View
    {
        $this->authorize('viewAny', Activity::class);
        $user = $request->user();
        [$activities, $allowedTabs, $tab, $pendingCount, $workspaceList] = app(ContentListing::class)
            ->scope(Activity::query()->with('author'), $request, 'activities');

        // SECURITY: Engagement queries are limited to Published and require both the feature flag and schema.
        $viewCountsEnabled = $tab === 'published' && $engagement->viewCountsAvailable($activities->getModel());
        // SECURITY: Like controls and ordering require the likes table and this module's counter column.
        $likesEnabled = $tab === 'published' && $engagement->likesAvailable()
            && $engagement->likeCountsAvailable($activities->getModel());
        if ($likesEnabled) {
            $activities->withCount('likes');
            if ($user) {
                $activities->withCount(['likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $user->user_id)]);
            }
        }

        // SECURITY: Sort keys map to fixed columns; date sorts keep records without an event date last.
        $sortMap = ['recent' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc']];
        if ($tab === 'published') {
            $sortMap['event_soonest'] = ['activity_date', 'asc'];
            $sortMap['event_latest'] = ['activity_date', 'desc'];
            if ($viewCountsEnabled) {
                $sortMap['viewed'] = ['view_count', 'desc'];
            }
            if ($likesEnabled) {
                $sortMap['liked'] = ['like_count', 'desc'];
            }
        }
        $sort = $request->query('sort');
        $sort = is_string($sort) && array_key_exists($sort, $sortMap) ? $sort : 'recent';
        $sortOptions = ['recent' => 'Most recent', 'oldest' => 'Oldest', 'title' => 'Title (A to Z)'];
        if ($tab === 'published') {
            $sortOptions += ['event_soonest' => 'Event date (soonest)', 'event_latest' => 'Event date (latest)'];
            if ($viewCountsEnabled) {
                $sortOptions['viewed'] = 'Most viewed';
            }
            if ($likesEnabled) {
                $sortOptions['liked'] = 'Most liked';
            }
        }
        $timeframe = $request->query('timeframe');
        $timeframeOptions = ['all' => 'All dates', 'upcoming' => 'Upcoming', 'past' => 'Past'];
        $timeframe = is_string($timeframe) && array_key_exists($timeframe, $timeframeOptions) ? $timeframe : 'all';
        if ($timeframe === 'upcoming') {
            $activities->whereNotNull('activity_date')->whereDate('activity_date', '>=', today());
        } elseif ($timeframe === 'past') {
            $activities->whereNotNull('activity_date')->whereDate('activity_date', '<', today());
        }
        [$sortColumn, $sortDirection] = $sortMap[$sort];
        if ($sortColumn === 'activity_date') {
            $activities->orderByRaw('activity_date IS NULL ASC');
        }
        $activities->orderBy($sortColumn, $sortDirection);
        if (in_array($sort, ['viewed', 'liked'], true)) {
            $activities->orderBy('created_at', 'desc');
        }
        $activities = $activities->orderBy('activity_id', 'asc')
            ->paginate(5)
            ->appends(['tab' => $tab, 'sort' => $sort, 'timeframe' => $timeframe]);
        // SECURITY: Visiting Activities clears only this user's unread publication notices for this tab.
        if ($user) {
            $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['activity'])->update(['read_at' => now()]);
        }

        // Use the management feed only after the role's tab allowlist has accepted the requested tab.
        $view = $workspaceList ? 'workspace.posts.activities.index' : 'activities.index';

        return view($view, compact('activities', 'allowedTabs', 'tab', 'sort', 'sortOptions', 'timeframe', 'timeframeOptions', 'pendingCount'));
    }

    /** Show public activities or private records to their author or the President. */
    public function show(Activity $activity, Request $request, PostEngagement $engagement): View
    {
        // SECURITY: Return 404 for policy denials so private record existence is not disclosed.
        try {
            $this->authorize('view', $activity);
        } catch (AuthorizationException) {
            abort(404);
        }
        if (! auth()->check() && ! $this->publiclyVisible($activity)) {
            abort(404);
        }
        $management = $request->routeIs('workspace.posts.*');
        if ($management) {
            abort_unless($request->user()?->canPostIn('activities'), 403);
        } else {
            abort_unless($activity->isApproved() && $activity->content_status === Activity::CONTENT_ACTIVE, 404);
        }
        // SECURITY: Count only after show authorization; the service excludes authors, bots, and nonpublic posts.
        $engagement->recordView($activity, $request);
        $engagement->loadLikeState($activity, $request);
        $activity->load(['author', 'reviewer']);

        return view('activities.show', compact('activity', 'management'));
    }

    /** Show the activity form only when the current position can post here. */
    public function create(): View
    {
        $this->authorize('create', Activity::class);

        return view('activities.create');
    }

    /** Store position-based submissions as pending or President posts as configured. */
    public function store(ActivityRequest $request): RedirectResponse
    {
        // Validate editable activity fields and the optional image before writing.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('create', Activity::class);
        $user = $request->user();
        $activity = new Activity($validated);
        $activity->created_by_user_id = $user->user_id;
        $activity->content_status = Activity::CONTENT_ACTIVE;
        $autoApprove = $user->postsAutoApproved();
        $activity->approval_status = $autoApprove ? Activity::APPROVAL_APPROVED : Activity::APPROVAL_PENDING;

        if ($autoApprove) {
            $activity->reviewed_by_user_id = $user->user_id;
            $activity->reviewed_at = now();
        }

        if ($request->hasFile('image')) {
            // SECURITY: Store a generated filename under this module's public-disk directory.
            $activity->image = $this->storeImage($request);
        }

        app(PostFiles::class)->save($activity);
        // SECURITY: Pending submissions notify configured reviewers; President posts follow the school toggle.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $activity->content_status === Activity::CONTENT_ACTIVE) {
            $notifications->published('activity', $activity, $user);
        } else {
            $notifications->submitted('activity', $activity, $user);
        }

        return redirect()->route('workspace.posts.activities.show', $activity)->with('status', 'The activity was created.');
    }

    /** Show the edit form to the owner with a current capability or the President. */
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('activities.edit', compact('activity'));
    }

    /** Update activity fields and return rejected or configured approved edits to review. */
    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        // Keep the same optional replacement image and activity field rules as creation.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('update', $activity);
        $wasApproved = $activity->isApproved();
        $wasRejected = $activity->isRejected();
        $activity->fill($validated);

        if ($request->hasFile('image')) {
            // SECURITY: Save a generated replacement before deleting the old module-owned file.
            $activity->image = $this->storeImage($request);
        }

        if ($wasRejected || ($wasApproved && $request->user()->isOfficer()
            && config('school.reset_approval_on_edit', true))) {
            // Changed content needs review again; approval and visibility remain separate states.
            $activity->approval_status = Activity::APPROVAL_PENDING;
            $activity->rejection_reason = null;
            $activity->reviewed_by_user_id = null;
            $activity->reviewed_at = null;
        }

        app(PostFiles::class)->save($activity);
        // SECURITY: Only officer edits returned to pending trigger a review notice; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('activity', $activity, $request->user(), true);
        }

        return redirect()->route('workspace.posts.activities.show', $activity)->with('status', 'The activity was updated.');
    }

    /** Archive an activity for its owner or the President without changing approval state. */
    public function archive(Activity $activity): RedirectResponse
    {
        $this->authorize('archive', $activity);
        if ($activity->isArchived()) {
            return back()->with('status', 'This activity is already archived.');
        }

        $activity->content_status = Activity::CONTENT_ARCHIVED;
        app(PostFiles::class)->save($activity);

        return back()->with('status', 'The activity was archived.');
    }

    /** Restore an activity while preserving its approval state. */
    public function restore(Activity $activity): RedirectResponse
    {
        $this->authorize('restore', $activity);
        if (! $activity->isArchived()) {
            return back()->with('status', 'This activity is already active.');
        }

        $activity->content_status = Activity::CONTENT_ACTIVE;
        app(PostFiles::class)->save($activity);

        return back()->with('status', 'The activity was restored.');
    }

    /** Approve only pending activities and report repeated review requests clearly. */
    public function approve(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('approve', $activity);
        if (! $activity->isPending()) {
            return back()->with('error', 'Only pending activities can be approved.');
        }

        if (! app(ContentReview::class)->decide($activity, $request->user(), true)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The activity was approved.');
    }

    /** Reject only pending activities after the President reason has been validated. */
    public function reject(Request $request, Activity $activity): RedirectResponse
    {
        // Keep President rejection reasons required and within the database limit.
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:255']], [
            'rejection_reason.required' => 'Enter a reason before rejecting this activity.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ]);
        $this->authorize('reject', $activity);
        if (! $activity->isPending()) {
            return back()->with('error', 'Only pending activities can be rejected.');
        }

        $reason = $validated['rejection_reason'];
        if (! app(ContentReview::class)->decide($activity, $request->user(), false, $reason)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The activity was rejected.');
    }

    /** Permanently delete an activity and only its image inside activities/. */
    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);
        app(PostFiles::class)->delete($activity);

        return redirect()->route('workspace.posts.activities.index', ['tab' => 'all'])->with('status', 'The activity was deleted.');
    }

    /** Archive President-selected activities after validating and authorizing every submitted ID. */
    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Activity::class);
        $validated = $request->validate([
            'activity_ids' => ['required', 'array', 'min:1', 'max:100'],
            'activity_ids.*' => ['required', 'integer', 'distinct', Rule::exists('activities', 'activity_id')],
        ]);

        $activities = Activity::query()->whereIn('activity_id', $validated['activity_ids'])->get();
        foreach ($activities as $activity) {
            $this->authorize('archive', $activity);
        }

        Activity::query()->whereIn('activity_id', $activities->modelKeys())
            ->update(['content_status' => Activity::CONTENT_ARCHIVED, 'updated_at' => now()]);

        return back()->with('status', 'The selected activities were archived.');
    }

    /** Check public state for guests, who have no user policy context. */
    private function publiclyVisible(Activity $activity): bool
    {
        return $activity->isApproved() && $activity->content_status === Activity::CONTENT_ACTIVE;
    }

    /** Save a validated upload under this module's public-disk image directory. */
    private function storeImage(Request $request): string
    {
        return app(PostFiles::class)->store($request->file('image'), 'activities', 'public');
    }
}
