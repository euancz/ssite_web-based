<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Rules\MaxWords;
use App\Support\PostNotificationRecipients;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/* Position-based submissions start pending; the President reviews and may publish directly.
 * Public queries require approved and active state; archive and restore preserve review state.
 */
/** Lists and manages achievements while enforcing position capabilities and review state server-side. */
class AchievementController extends Controller
{
    use AuthorizesRequests;

    /** Show the tabs and records available to the current capability. */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Achievement::class);
        $user = $request->user();
        $allowedTabs = $user?->canReview()
            ? ['published', 'pending', 'rejected', 'archived', 'all']
            : ($user?->canPostIn('achievements') ? ['published', 'mine', 'archived'] : ['published']);
        // SECURITY: Pending totals are queried only for review-capable users and never reach public or student views.
        $pendingCount = $user?->canReview() ? Achievement::pending()->count() : null;
        $tab = $request->query('tab', 'published');

        // SECURITY: Unknown or role-hidden tabs fall back to Published before querying records.
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'published';
        }

        $achievements = Achievement::query()->with('author');
        if ($tab === 'published') {
            $achievements->publiclyVisible();
        } elseif ($tab === 'mine' && $user) {
            $achievements->where('created_by_user_id', $user->user_id);
        } elseif ($tab === 'archived') {
            $achievements->archived();
            if (! $user?->isPresident()) {
                $achievements->where('created_by_user_id', $user->user_id);
            }
        } elseif ($tab === 'pending') {
            $achievements->pending();
        } elseif ($tab === 'rejected') {
            $achievements->rejected();
        }

        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $achievements->when($search !== '', function ($query) use ($search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('awardee', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%')
                    ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%' . $search . '%'));
            });
        });

        $achievements = $achievements->latest('created_at')->paginate(10)->withQueryString();
        // SECURITY: Visiting Achievements clears only this user's unread publication notices for this tab.
        if ($user) $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['achievement'])->update(['read_at' => now()]);

        return view('achievements.index', compact('achievements', 'allowedTabs', 'tab', 'search', 'pendingCount'));
    }

    /** Show public achievements or private records to their author or the President. */
    public function show(Achievement $achievement): View
    {
        // SECURITY: Return 404 for policy denials so private record existence is not disclosed.
        try {
            $this->authorize('view', $achievement);
        } catch (AuthorizationException) {
            abort(404);
        }
        if (! auth()->check() && ! $this->publiclyVisible($achievement)) {
            abort(404);
        }
        $achievement->load(['author', 'reviewer']);

        return view('achievements.show', compact('achievement'));
    }

    /** Show the achievement form only when the current position can post here. */
    public function create(): View
    {
        $this->authorize('create', Achievement::class);

        return view('achievements.create');
    }

    /** Store position-based submissions as pending or President posts as configured. */
    public function store(Request $request): RedirectResponse
    {
        // Validate editable achievement fields and the optional image before writing.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', new MaxWords],
            'awardee' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'achievement_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'title.required' => 'Enter an achievement title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'description.required' => 'Enter the achievement description.',
            'awardee.max' => 'The awardee may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'achievement_date.date' => 'Enter a valid achievement date.',
            'image.image' => 'Choose a valid image file.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ]);
        $this->authorize('create', Achievement::class);
        $user = $request->user();
        $achievement = new Achievement($validated);
        $achievement->created_by_user_id = $user->user_id;
        $achievement->content_status = Achievement::CONTENT_ACTIVE;
        $autoApprove = $user->postsAutoApproved();
        $achievement->approval_status = $autoApprove ? Achievement::APPROVAL_APPROVED : Achievement::APPROVAL_PENDING;

        if ($autoApprove) {
            $achievement->reviewed_by_user_id = $user->user_id;
            $achievement->reviewed_at = now();
        }

        if ($request->hasFile('image')) {
            // SECURITY: Store a generated filename under this module's public-disk directory.
            $achievement->image = $this->storeImage($request);
        }

        $achievement->save();
        // SECURITY: Pending submissions notify configured reviewers; President posts follow the school toggle.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $achievement->content_status === Achievement::CONTENT_ACTIVE) $notifications->published('achievement', $achievement, $user);
        else $notifications->submitted('achievement', $achievement, $user);

        return redirect()->route('achievements.show', $achievement)->with('status', 'The achievement was created.');
    }

    /** Show the edit form to the owner with a current capability or the President. */
    public function edit(Achievement $achievement): View
    {
        $this->authorize('update', $achievement);

        return view('achievements.edit', compact('achievement'));
    }

    /** Update achievement fields and return rejected or configured approved edits to review. */
    public function update(Request $request, Achievement $achievement): RedirectResponse
    {
        // Keep replacement images optional and retain the same field validation as creation.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', new MaxWords],
            'awardee' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'achievement_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'title.required' => 'Enter an achievement title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'description.required' => 'Enter the achievement description.',
            'awardee.max' => 'The awardee may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'achievement_date.date' => 'Enter a valid achievement date.',
            'image.image' => 'Choose a valid image file.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image may not be larger than 2 MB.',
        ]);
        $this->authorize('update', $achievement);
        $wasApproved = $achievement->isApproved();
        $wasRejected = $achievement->isRejected();
        $oldImage = $achievement->image;
        $achievement->fill($validated);

        if ($request->hasFile('image')) {
            // SECURITY: Save a generated replacement before deleting the old module-owned file.
            $achievement->image = $this->storeImage($request);
        }

        if ($wasRejected || ($wasApproved && $request->user()->isOfficer()
            && config('school.reset_approval_on_edit', true))) {
            // Changed content needs review again; approval and visibility remain separate states.
            $achievement->approval_status = Achievement::APPROVAL_PENDING;
            $achievement->rejection_reason = null;
            $achievement->reviewed_by_user_id = null;
            $achievement->reviewed_at = null;
        }

        $achievement->save();
        // SECURITY: Edits returned to pending notify configured reviewers; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('achievement', $achievement, $request->user(), true);
        }
        if ($oldImage && $oldImage !== $achievement->image) {
            $this->deleteStoredImage($oldImage);
        }

        return redirect()->route('achievements.show', $achievement)->with('status', 'The achievement was updated.');
    }

    /** Archive an achievement for its owner or the President without changing approval state. */
    public function archive(Achievement $achievement): RedirectResponse
    {
        $this->authorize('archive', $achievement);
        if ($achievement->isArchived()) {
            return back()->with('status', 'This achievement is already archived.');
        }

        $achievement->content_status = Achievement::CONTENT_ARCHIVED;
        $achievement->save();

        return back()->with('status', 'The achievement was archived.');
    }

    /** Restore an achievement while preserving its approval state. */
    public function restore(Achievement $achievement): RedirectResponse
    {
        $this->authorize('restore', $achievement);
        if (! $achievement->isArchived()) {
            return back()->with('status', 'This achievement is already active.');
        }

        $achievement->content_status = Achievement::CONTENT_ACTIVE;
        $achievement->save();

        return back()->with('status', 'The achievement was restored.');
    }

    /** Approve only pending achievements and report repeated review requests clearly. */
    public function approve(Request $request, Achievement $achievement): RedirectResponse
    {
        $this->authorize('approve', $achievement);
        if (! $achievement->isPending()) {
            return back()->with('error', 'Only pending achievements can be approved.');
        }

        // SECURITY: A conditional state change ensures concurrent clicks can send only one notification set.
        $reviewedAt = now();
        $changed = $achievement->newQuery()->whereKey($achievement->getKey())->where('approval_status', Achievement::APPROVAL_PENDING)
            ->update(['approval_status' => Achievement::APPROVAL_APPROVED, 'rejection_reason' => null,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => $reviewedAt]);
        if (! $changed) return back()->with('error', 'Only pending achievements can be approved.');
        $achievement->approval_status = Achievement::APPROVAL_APPROVED;
        app(PostNotificationRecipients::class)->approved('achievement', $achievement, $request->user());

        return back()->with('status', 'The achievement was approved.');
    }

    /** Reject only pending achievements after the President reason has been validated. */
    public function reject(Request $request, Achievement $achievement): RedirectResponse
    {
        // Keep President rejection reasons required and within the database limit.
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:255']], [
            'rejection_reason.required' => 'Enter a reason before rejecting this achievement.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ]);
        $this->authorize('reject', $achievement);
        if (! $achievement->isPending()) {
            return back()->with('error', 'Only pending achievements can be rejected.');
        }

        $reason = $validated['rejection_reason'];
        $changed = $achievement->newQuery()->whereKey($achievement->getKey())->where('approval_status', Achievement::APPROVAL_PENDING)
            ->update(['approval_status' => Achievement::APPROVAL_REJECTED, 'rejection_reason' => $reason,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => now()]);
        if (! $changed) return back()->with('error', 'Only pending achievements can be rejected.');
        $achievement->approval_status = Achievement::APPROVAL_REJECTED;
        $achievement->rejection_reason = $reason;
        app(PostNotificationRecipients::class)->rejected('achievement', $achievement, $request->user(), $reason);

        return back()->with('status', 'The achievement was rejected.');
    }

    /** Permanently delete an achievement and only its image inside achievements/. */
    public function destroy(Achievement $achievement): RedirectResponse
    {
        $this->authorize('delete', $achievement);
        if ($achievement->image) {
            $this->deleteStoredImage($achievement->image);
        }
        $achievement->delete();

        return redirect()->route('achievements.index', ['tab' => 'all'])->with('status', 'The achievement was deleted.');
    }

    /** Archive President-selected achievements after validating and authorizing every submitted ID. */
    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Achievement::class);
        $validated = $request->validate([
            'achievement_ids' => ['required', 'array', 'min:1', 'max:100'],
            'achievement_ids.*' => ['required', 'integer', 'distinct', Rule::exists('achievements', 'achievement_id')],
        ]);

        $achievements = Achievement::query()->whereIn('achievement_id', $validated['achievement_ids'])->get();
        foreach ($achievements as $achievement) {
            $this->authorize('archive', $achievement);
        }

        Achievement::query()->whereIn('achievement_id', $achievements->modelKeys())
            ->update(['content_status' => Achievement::CONTENT_ARCHIVED, 'updated_at' => now()]);

        return back()->with('status', 'The selected achievements were archived.');
    }

    /** Check public state for guests, who have no user policy context. */
    private function publiclyVisible(Achievement $achievement): bool
    {
        return $achievement->isApproved() && $achievement->content_status === Achievement::CONTENT_ACTIVE;
    }

    /** Save a validated upload under this module's public-disk image directory. */
    private function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::uuid() . '.' . ($file->guessExtension() ?: 'bin');

        return $file->storeAs('achievements', $filename, 'public');
    }

    /** Ignore database paths outside the generated achievement image filename pattern. */
    private function deleteStoredImage(string $path): void
    {
        if (preg_match('#^achievements/[A-Za-z0-9-]+\.(jpg|jpeg|png|webp)$#i', $path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
