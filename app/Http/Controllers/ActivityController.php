<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activity\RejectActivityRequest;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Models\Activity;
use App\Support\PostNotificationRecipients;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/*
 * Officer activities start pending; advisers approve or reject with a reason. Only approved
 * AND active activities appear publicly; archive shelves content without changing review state,
 * and restore returns it to active while preserving pending or rejected approval state. Archive,
 * restore, and delete stay silent; only review and publication transitions send notifications.
 */
/** Lists, edits, reviews, and archives activities with server-side role and state checks. */
class ActivityController extends Controller
{
    use AuthorizesRequests;

    /** Show each role only the activity tabs and records available to that role. */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);
        $user = $request->user();
        $allowedTabs = $user?->isAdviser()
            ? ['published', 'pending', 'rejected', 'archived', 'all']
            : ($user?->isOfficer() ? ['published', 'mine', 'archived'] : ['published']);
        $tab = $request->query('tab', 'published');

        // SECURITY: Unknown or role-hidden tabs fall back to Published before querying records.
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'published';
        }

        $activities = Activity::query()->with('author');
        if ($tab === 'published') {
            $activities->publiclyVisible();
        } elseif ($tab === 'mine' && $user) {
            $activities->where('created_by_user_id', $user->user_id);
        } elseif ($tab === 'archived') {
            $activities->archived();
            if (! $user?->isAdviser()) {
                $activities->where('created_by_user_id', $user->user_id);
            }
        } elseif ($tab === 'pending') {
            $activities->pending();
        } elseif ($tab === 'rejected') {
            $activities->rejected();
        }

        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $activities->when($search !== '', function ($query) use ($search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%')
                    ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%' . $search . '%'));
            });
        });

        $activities = $activities->latest('created_at')->paginate(10)->withQueryString();
        // SECURITY: Visiting Activities clears only this user's unread publication notices for this tab.
        if ($user) $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['activity'])->update(['read_at' => now()]);

        return view('activities.index', compact('activities', 'allowedTabs', 'tab', 'search'));
    }

    /** Show public activities or private records to their author or an adviser. */
    public function show(Activity $activity): View
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
        $activity->load(['author', 'reviewer']);

        return view('activities.show', compact('activity'));
    }

    /** Show the activity form to officers and advisers. */
    public function create(): View
    {
        $this->authorize('create', Activity::class);

        return view('activities.create');
    }

    /** Store officer submissions as pending and adviser submissions according to school settings. */
    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $this->authorize('create', Activity::class);
        $user = $request->user();
        $activity = new Activity($request->validated());
        $activity->created_by_user_id = $user->user_id;
        $activity->content_status = Activity::CONTENT_ACTIVE;
        $autoApprove = $user->isAdviser() && config('school.adviser_posts_auto_approved', true);
        $activity->approval_status = $autoApprove ? Activity::APPROVAL_APPROVED : Activity::APPROVAL_PENDING;

        if ($autoApprove) {
            $activity->reviewed_by_user_id = $user->user_id;
            $activity->reviewed_at = now();
        }

        if ($request->hasFile('image')) {
            // SECURITY: Store a generated filename under this module's public-disk directory.
            $activity->image = $this->storeImage($request);
        }

        $activity->save();
        // Pending submissions go to advisers only; adviser auto-approved posts are already published.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $activity->content_status === Activity::CONTENT_ACTIVE) $notifications->published('activity', $activity, $user);
        elseif ($user->isOfficer()) $notifications->submitted('activity', $activity, $user);

        return redirect()->route('activities.show', $activity)->with('status', 'The activity was created.');
    }

    /** Show the edit form to the activity owner or an adviser. */
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('activities.edit', compact('activity'));
    }

    /** Update activity fields and return rejected or configured approved officer edits to review. */
    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);
        $wasApproved = $activity->isApproved();
        $wasRejected = $activity->isRejected();
        $oldImage = $activity->image;
        $activity->fill($request->validated());

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

        $activity->save();
        // SECURITY: Only officer edits returned to pending trigger a review notice; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('activity', $activity, $request->user(), true);
        }
        if ($oldImage && $oldImage !== $activity->image) {
            $this->deleteStoredImage($oldImage);
        }

        return redirect()->route('activities.show', $activity)->with('status', 'The activity was updated.');
    }

    /** Archive an activity for its owner or an adviser without changing approval state. */
    public function archive(Activity $activity): RedirectResponse
    {
        $this->authorize('archive', $activity);
        if ($activity->isArchived()) {
            return back()->with('status', 'This activity is already archived.');
        }

        $activity->content_status = Activity::CONTENT_ARCHIVED;
        $activity->save();

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
        $activity->save();

        return back()->with('status', 'The activity was restored.');
    }

    /** Approve only pending activities and report repeated review requests clearly. */
    public function approve(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('approve', $activity);
        if (! $activity->isPending()) {
            return back()->with('error', 'Only pending activities can be approved.');
        }

        // SECURITY: A conditional state change ensures concurrent clicks can send only one notification set.
        $reviewedAt = now();
        $changed = $activity->newQuery()->whereKey($activity->getKey())->where('approval_status', Activity::APPROVAL_PENDING)
            ->update(['approval_status' => Activity::APPROVAL_APPROVED, 'rejection_reason' => null,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => $reviewedAt]);
        if (! $changed) return back()->with('error', 'Only pending activities can be approved.');
        $activity->approval_status = Activity::APPROVAL_APPROVED;
        app(PostNotificationRecipients::class)->approved('activity', $activity, $request->user());

        return back()->with('status', 'The activity was approved.');
    }

    /** Reject only pending activities after the adviser reason has been validated. */
    public function reject(RejectActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('reject', $activity);
        if (! $activity->isPending()) {
            return back()->with('error', 'Only pending activities can be rejected.');
        }

        $reason = $request->validated('rejection_reason');
        $changed = $activity->newQuery()->whereKey($activity->getKey())->where('approval_status', Activity::APPROVAL_PENDING)
            ->update(['approval_status' => Activity::APPROVAL_REJECTED, 'rejection_reason' => $reason,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => now()]);
        if (! $changed) return back()->with('error', 'Only pending activities can be rejected.');
        $activity->approval_status = Activity::APPROVAL_REJECTED;
        $activity->rejection_reason = $reason;
        app(PostNotificationRecipients::class)->rejected('activity', $activity, $request->user(), $reason);

        return back()->with('status', 'The activity was rejected.');
    }

    /** Permanently delete an activity and only its image inside activities/. */
    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);
        if ($activity->image) {
            $this->deleteStoredImage($activity->image);
        }
        $activity->delete();

        return redirect()->route('activities.index', ['tab' => 'all'])->with('status', 'The activity was deleted.');
    }

    /** Archive adviser-selected activities after validating and authorizing every submitted ID. */
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
        $file = $request->file('image');
        $filename = Str::uuid() . '.' . ($file->guessExtension() ?: 'bin');

        return $file->storeAs('activities', $filename, 'public');
    }

    /** Ignore database paths outside the generated activity image filename pattern. */
    private function deleteStoredImage(string $path): void
    {
        if (preg_match('#^activities/[A-Za-z0-9-]+\.(jpg|jpeg|png|webp)$#i', $path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
