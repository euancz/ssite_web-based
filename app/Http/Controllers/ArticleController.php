<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\RejectArticleRequest;
use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Models\Article;
use App\Support\PostNotificationRecipients;
use Illuminate\Contracts\View\View;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/*
 * Officer articles start pending; advisers approve or reject with a reason. Only approved
 * AND active articles appear publicly; archive shelves content without changing review state,
 * and restore returns it to active while preserving pending or rejected approval state. Archive,
 * restore, and delete stay silent; only review and publication transitions send notifications.
 */
/**
 * Lists, edits, reviews, and archives Articles while keeping role and state rules server-side.
 */
class ArticleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show each role only the article tabs and rows that role may access.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Article::class);
        $user = $request->user();
        $allowedTabs = $user?->isAdviser()
            ? ['published', 'pending', 'rejected', 'archived', 'all']
            : ($user?->isOfficer() ? ['published', 'mine', 'archived'] : ['published']);
        $tab = $request->query('tab', 'published');

        // SECURITY: Unknown or role-hidden tabs fall back to Published before building the query.
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'published';
        }

        $articles = Article::query()->with('author');
        if ($tab === 'published') {
            $articles->publiclyVisible();
        } elseif ($tab === 'mine' && $user) {
            $articles->where('created_by_user_id', $user->user_id);
        } elseif ($tab === 'archived') {
            $articles->archived();
            if (! $user?->isAdviser()) {
                $articles->where('created_by_user_id', $user->user_id);
            }
        } elseif ($tab === 'pending') {
            $articles->pending();
        } elseif ($tab === 'rejected') {
            $articles->rejected();
        }

        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $articles->when($search !== '', function ($query) use ($search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%' . $search . '%'));
            });
        });

        $articles = $articles->latest('created_at')->paginate(10)->withQueryString();

        // SECURITY: Visiting Articles clears only this user's unread publication notices for this tab.
        if ($user) $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['article'])->update(['read_at' => now()]);

        return view('articles.index', compact('articles', 'allowedTabs', 'tab', 'search'));
    }

    /**
     * Show a public article or a private article to its author or an adviser.
     */
    public function show(Article $article): View
    {
        // SECURITY: Convert policy denial to 404 so private article existence is not disclosed.
        try {
            $this->authorize('view', $article);
        } catch (AuthorizationException) {
            abort(404);
        }
        if (! auth()->check() && ! $this->publiclyVisible($article)) {
            abort(404);
        }
        $article->load(['author', 'reviewer']);

        return view('articles.show', compact('article'));
    }

    /**
     * Show the create form to officers and advisers.
     */
    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('articles.create');
    }

    /**
     * Store officer submissions as pending and adviser submissions per the school setting.
     */
    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $user = $request->user();
        $article = new Article($request->validated());
        $article->created_by_user_id = $user->user_id;
        $article->content_status = Article::CONTENT_ACTIVE;
        $autoApprove = $user->isAdviser() && config('school.adviser_posts_auto_approved', true);
        $article->approval_status = $autoApprove ? Article::APPROVAL_APPROVED : Article::APPROVAL_PENDING;

        if ($autoApprove) {
            $article->reviewed_by_user_id = $user->user_id;
            $article->reviewed_at = now();
        }

        if ($request->hasFile('image')) {
            // SECURITY: Generate a unique server-side filename and store only its public-disk path.
            $article->image = $this->storeImage($request);
        }

        $article->save();

        // Pending submissions go to advisers only; adviser auto-approved posts are already published.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $article->content_status === Article::CONTENT_ACTIVE) $notifications->published('article', $article, $user);
        elseif ($user->isOfficer()) $notifications->submitted('article', $article, $user);

        return redirect()->route('articles.show', $article)->with('status', 'The article was created.');
    }

    /**
     * Show the edit form to the article owner or an adviser.
     */
    public function edit(Article $article): View
    {
        $this->authorize('update', $article);

        return view('articles.edit', compact('article'));
    }

    /**
     * Update article content and reset review when a rejected or approved officer post changes.
     */
    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);
        $wasApproved = $article->isApproved();
        $wasRejected = $article->isRejected();
        $oldImage = $article->image;
        $article->fill($request->validated());

        if ($request->hasFile('image')) {
            // SECURITY: Store the replacement with a generated name before forgetting the old path.
            $article->image = $this->storeImage($request);
        }

        if ($wasRejected || ($wasApproved && $request->user()->isOfficer()
            && config('school.reset_approval_on_edit', true))) {
            // Edited content needs a fresh adviser decision; approval and visibility stay separate.
            $article->approval_status = Article::APPROVAL_PENDING;
            $article->rejection_reason = null;
            $article->reviewed_by_user_id = null;
            $article->reviewed_at = null;
        }

        $article->save();
        // SECURITY: Only officer edits returned to pending trigger a review notice; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('article', $article, $request->user(), true);
        }
        if ($oldImage && $oldImage !== $article->image) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('articles.show', $article)->with('status', 'The article was updated.');
    }

    /**
     * Archive an article for its owner or an adviser without changing review state.
     */
    public function archive(Article $article): RedirectResponse
    {
        $this->authorize('archive', $article);
        if ($article->isArchived()) {
            return back()->with('status', 'This article is already archived.');
        }

        $article->content_status = Article::CONTENT_ARCHIVED;
        $article->save();

        return back()->with('status', 'The article was archived.');
    }

    /**
     * Restore an article for its owner or an adviser while preserving its review state.
     */
    public function restore(Article $article): RedirectResponse
    {
        $this->authorize('restore', $article);
        if (! $article->isArchived()) {
            return back()->with('status', 'This article is already active.');
        }

        $article->content_status = Article::CONTENT_ACTIVE;
        $article->save();

        return back()->with('status', 'The article was restored.');
    }

    /**
     * Approve only pending articles and return a friendly message for repeated requests.
     */
    public function approve(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('approve', $article);
        if (! $article->isPending()) {
            return back()->with('error', 'Only pending articles can be approved.');
        }

        // SECURITY: A conditional state change ensures concurrent clicks can send only one notification set.
        $reviewedAt = now();
        $changed = $article->newQuery()->whereKey($article->getKey())->where('approval_status', Article::APPROVAL_PENDING)
            ->update(['approval_status' => Article::APPROVAL_APPROVED, 'rejection_reason' => null,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => $reviewedAt]);
        if (! $changed) return back()->with('error', 'Only pending articles can be approved.');
        $article->approval_status = Article::APPROVAL_APPROVED;
        app(PostNotificationRecipients::class)->approved('article', $article, $request->user());

        return back()->with('status', 'The article was approved.');
    }

    /**
     * Reject only pending articles after validating an adviser reason.
     */
    public function reject(RejectArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('reject', $article);
        if (! $article->isPending()) {
            return back()->with('error', 'Only pending articles can be rejected.');
        }

        $reason = $request->validated('rejection_reason');
        $changed = $article->newQuery()->whereKey($article->getKey())->where('approval_status', Article::APPROVAL_PENDING)
            ->update(['approval_status' => Article::APPROVAL_REJECTED, 'rejection_reason' => $reason,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => now()]);
        if (! $changed) return back()->with('error', 'Only pending articles can be rejected.');
        $article->approval_status = Article::APPROVAL_REJECTED;
        $article->rejection_reason = $reason;
        app(PostNotificationRecipients::class)->rejected('article', $article, $request->user(), $reason);

        return back()->with('status', 'The article was rejected.');
    }

    /**
     * Permanently delete an article and its stored image for advisers only.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);
        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }
        $article->delete();

        return redirect()->route('articles.index', ['tab' => 'all'])->with('status', 'The article was deleted.');
    }

    /**
     * Archive adviser-selected articles after validating and authorizing every submitted ID.
     */
    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Article::class);
        $validated = $request->validate([
            'article_ids' => ['required', 'array', 'min:1', 'max:100'],
            'article_ids.*' => ['required', 'integer', 'distinct', Rule::exists('articles', 'article_id')],
        ]);

        $articles = Article::query()->whereIn('article_id', $validated['article_ids'])->get();
        foreach ($articles as $article) {
            $this->authorize('archive', $article);
        }

        Article::query()->whereIn('article_id', $articles->modelKeys())
            ->update(['content_status' => Article::CONTENT_ARCHIVED, 'updated_at' => now()]);

        return back()->with('status', 'The selected articles were archived.');
    }

    /**
     * Check public state for guest show requests where no user policy can be called.
     */
    private function publiclyVisible(Article $article): bool
    {
        return $article->isApproved() && $article->content_status === Article::CONTENT_ACTIVE;
    }

    /**
     * Save a validated image under a generated path on the public disk.
     */
    private function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::uuid() . '.' . ($file->guessExtension() ?: 'bin');

        return $file->storeAs('articles', $filename, 'public');
    }
}
