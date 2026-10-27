<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\ArticleRequest;
use App\Models\Article;
use App\Services\ContentDrafts;
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
/**
 * Lists and manages Articles while enforcing position capabilities and review state server-side.
 */
class ArticleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show each role only the article tabs and rows that role may access.
     */
    public function index(Request $request, PostEngagement $engagement): View
    {
        $this->authorize('viewAny', Article::class);
        $user = $request->user();
        [$articles, $allowedTabs, $tab, $pendingCount, $workspaceList] = app(ContentListing::class)
            ->scope(Article::query()->with('author'), $request, 'articles');

        // SECURITY: Engagement queries are limited to Published and require both the feature flag and schema.
        $viewCountsEnabled = $tab === 'published' && $engagement->viewCountsAvailable($articles->getModel());
        // SECURITY: Like controls and ordering require the likes table and this module's counter column.
        $likesEnabled = $tab === 'published' && $engagement->likesAvailable()
            && $engagement->likeCountsAvailable($articles->getModel());
        if ($likesEnabled) {
            $articles->withCount('likes');
            if ($user) {
                $articles->withCount(['likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $user->user_id)]);
            }
        }

        // SECURITY: Request values select a fixed column/direction pair; they never become SQL identifiers.
        $sortMap = [
            'recent' => ['created_at', 'desc'],
            'oldest' => ['created_at', 'asc'],
            'title' => ['title', 'asc'],
        ];
        if ($viewCountsEnabled) {
            $sortMap['viewed'] = ['view_count', 'desc'];
        }
        if ($likesEnabled) {
            $sortMap['liked'] = ['like_count', 'desc'];
        }
        $sort = $request->query('sort');
        $sort = is_string($sort) && array_key_exists($sort, $sortMap) ? $sort : 'recent';
        $sortOptions = ['recent' => 'Most recent', 'oldest' => 'Oldest', 'title' => 'Title (A to Z)'];
        if ($viewCountsEnabled) {
            $sortOptions['viewed'] = 'Most viewed';
        }
        if ($likesEnabled) {
            $sortOptions['liked'] = 'Most liked';
        }
        [$sortColumn, $sortDirection] = $sortMap[$sort];
        $articles->orderBy($sortColumn, $sortDirection);
        if (in_array($sort, ['viewed', 'liked'], true)) {
            $articles->orderBy('created_at', 'desc');
        }
        $articles = $articles->orderBy('article_id', 'asc')
            ->paginate(5)
            ->appends(['q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '', 'tab' => $tab, 'sort' => $sort]);

        // SECURITY: Visiting Articles clears only this user's unread publication notices for this tab.
        if ($user) {
            $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['article'])->update(['read_at' => now()]);
        }

        // Use the management feed only after the role's tab allowlist has accepted the requested tab.
        $view = $workspaceList ? 'workspace.posts.articles.index' : 'articles.index';

        return view($view, compact('articles', 'allowedTabs', 'tab', 'sort', 'sortOptions', 'pendingCount'));
    }

    /**
     * Show a public article or a private article to its author or the President.
     */
    public function show(Article $article, Request $request, PostEngagement $engagement): View
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
        $management = $request->routeIs('workspace.posts.*');
        if ($management) {
            abort_unless($request->user()?->canPostIn('articles'), 403);
        } else {
            abort_unless($article->isApproved() && $article->content_status === Article::CONTENT_ACTIVE, 404);
        }
        // SECURITY: Count only after show authorization; the service excludes authors, bots, and nonpublic posts.
        $engagement->recordView($article, $request);
        $engagement->loadLikeState($article, $request);
        $article->load(['author', 'reviewer']);

        return view('articles.show', compact('article', 'management'));
    }

    /**
     * Show the create form only when the current position can post here.
     */
    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('articles.create');
    }

    /**
     * Store position-based submissions as pending or President posts as configured.
     */
    public function store(ArticleRequest $request): RedirectResponse
    {
        // Validate only editable article fields; policy checks still protect the write below.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('create', Article::class);
        $user = $request->user();
        $article = new Article($validated);
        $article->created_by_user_id = $user->user_id;
        $article->content_status = Article::CONTENT_ACTIVE;
        $autoApprove = $user->postsAutoApproved();
        $article->approval_status = $autoApprove ? Article::APPROVAL_APPROVED : Article::APPROVAL_PENDING;

        if ($autoApprove) {
            $article->reviewed_by_user_id = $user->user_id;
            $article->reviewed_at = now();
        }

        if ($request->hasFile('image')) {
            // SECURITY: Generate a unique server-side filename and store only its public-disk path.
            $article->image = $this->storeImage($request);
        }

        app(ContentDrafts::class)->submit($article, $request);

        // SECURITY: Pending submissions notify configured reviewers; President posts follow the school toggle.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $article->content_status === Article::CONTENT_ACTIVE) {
            $notifications->published('article', $article, $user);
        } else {
            $notifications->submitted('article', $article, $user);
        }

        return redirect()->route('workspace.posts.articles.show', $article)->with('status', 'The article was created.');
    }

    /**
     * Show the edit form to the owner with a current capability or the President.
     */
    public function edit(Article $article): View
    {
        $this->authorize('update', $article);

        return view('articles.edit', compact('article'));
    }

    /**
     * Update article content and reset review when a rejected or approved post changes.
     */
    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        // Keep replacement images optional and validate the same editable fields as creation.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('update', $article);
        $wasApproved = $article->isApproved();
        $wasRejected = $article->isRejected();
        $article->fill($validated);

        if ($request->hasFile('image')) {
            // SECURITY: Store the replacement with a generated name before forgetting the old path.
            $article->image = $this->storeImage($request);
        }

        if ($wasRejected || ($wasApproved && $request->user()->isOfficer()
            && config('school.reset_approval_on_edit', true))) {
            // Edited content needs a fresh President decision; approval and visibility stay separate.
            $article->approval_status = Article::APPROVAL_PENDING;
            $article->rejection_reason = null;
            $article->reviewed_by_user_id = null;
            $article->reviewed_at = null;
        }

        app(PostFiles::class)->save($article);
        // SECURITY: Only officer edits returned to pending trigger a review notice; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('article', $article, $request->user(), true);
        }

        return redirect()->route('workspace.posts.articles.show', $article)->with('status', 'The article was updated.');
    }

    /**
     * Archive an article for its owner or the President without changing review state.
     */
    public function archive(Article $article): RedirectResponse
    {
        $this->authorize('archive', $article);
        if ($article->isArchived()) {
            return back()->with('status', 'This article is already archived.');
        }

        $article->content_status = Article::CONTENT_ARCHIVED;
        app(PostFiles::class)->save($article);

        return back()->with('status', 'The article was archived.');
    }

    /**
     * Restore an article for its owner or the President while preserving its review state.
     */
    public function restore(Article $article): RedirectResponse
    {
        $this->authorize('restore', $article);
        if (! $article->isArchived()) {
            return back()->with('status', 'This article is already active.');
        }

        $article->content_status = Article::CONTENT_ACTIVE;
        app(PostFiles::class)->save($article);

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

        if (! app(ContentReview::class)->decide($article, $request->user(), true)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The article was approved.');
    }

    /**
     * Reject only pending articles after validating a President reason.
     */
    public function reject(Request $request, Article $article): RedirectResponse
    {
        // A rejection always includes the reason shown to the author.
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:255']], [
            'rejection_reason.required' => 'Enter a reason before rejecting this article.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ]);
        $this->authorize('reject', $article);
        if (! $article->isPending()) {
            return back()->with('error', 'Only pending articles can be rejected.');
        }

        $reason = $validated['rejection_reason'];
        if (! app(ContentReview::class)->decide($article, $request->user(), false, $reason)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The article was rejected.');
    }

    /**
     * Permanently delete an article and its stored image for the President.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);
        app(PostFiles::class)->delete($article);

        return redirect()->route('workspace.posts.articles.index', ['tab' => 'all'])->with('status', 'The article was deleted.');
    }

    /**
     * Archive President-selected articles after validating and authorizing every submitted ID.
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
        return app(PostFiles::class)->store($request->file('image'), 'articles', 'public');
    }
}
