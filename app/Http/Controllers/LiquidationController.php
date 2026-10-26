<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\LiquidationRequest;
use App\Models\Liquidation;
use App\Services\ContentReview;
use App\Services\PostFiles;
use App\Support\ContentListing;
use App\Support\PostEngagement;
use App\Support\PostNotificationRecipients;
use App\Support\PrivatePdfResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/* Position-based submissions start pending; the President reviews and may publish directly.
 * PDF bytes stay private behind authorized routes; archive and restore preserve review state.
 */
class LiquidationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, PostEngagement $engagement): View
    {
        $this->authorize('viewAny', Liquidation::class);
        $user = $request->user();
        [$liquidations, $allowedTabs, $tab, $pendingCount, $workspaceList] = app(ContentListing::class)
            ->scope(Liquidation::query()->with('author'), $request, 'liquidations');

        $searchQuery = $request->query('q');
        if (is_string($searchQuery) && trim($searchQuery) !== '') {
            $searchQuery = mb_substr(trim($searchQuery), 0, 100);
            $liquidations->where(function ($query) use ($searchQuery): void {
                $query->where('title', 'like', '%'.$searchQuery.'%')
                    ->orWhere('description', 'like', '%'.$searchQuery.'%')
                    ->orWhere('original_name', 'like', '%'.$searchQuery.'%');
            });
        } else {
            $searchQuery = '';
        }
        // SECURITY: Engagement queries are limited to Published and require both the feature flag and schema.
        $viewCountsEnabled = $tab === 'published' && $engagement->viewCountsAvailable($liquidations->getModel());
        // SECURITY: Like controls and ordering require the likes table and this module's counter column.
        $likesEnabled = $tab === 'published' && $engagement->likesAvailable()
            && $engagement->likeCountsAvailable($liquidations->getModel());
        if ($likesEnabled) {
            $liquidations->withCount('likes');
            if ($user) {
                $liquidations->withCount(['likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $user->user_id)]);
            }
        }

        // SECURITY: Only fixed sort keys reach ordering; report amounts remain confined to authorized list viewers.
        $sortMap = ['recent' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc']];
        if ($tab === 'published') {
            $sortMap['report_soonest'] = ['report_date', 'asc'];
            $sortMap['report_latest'] = ['report_date', 'desc'];
            $sortMap['amount_high'] = ['amount', 'desc'];
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
            $sortOptions += [
                'report_soonest' => 'Report date (soonest)',
                'report_latest' => 'Report date (latest)',
                'amount_high' => 'Amount (high to low)',
            ];
            if ($viewCountsEnabled) {
                $sortOptions['viewed'] = 'Most viewed';
            }
            if ($likesEnabled) {
                $sortOptions['liked'] = 'Most liked';
            }
        }
        [$sortColumn, $sortDirection] = $sortMap[$sort];
        if ($sortColumn === 'report_date') {
            $liquidations->orderByRaw('report_date IS NULL ASC');
        }
        $liquidations->orderBy($sortColumn, $sortDirection);
        if (in_array($sort, ['viewed', 'liked'], true)) {
            $liquidations->orderBy('created_at', 'desc');
        }
        $liquidations = $liquidations->orderBy('liquidation_id', 'asc')
            ->paginate(5)
            ->appends(['tab' => $tab, 'sort' => $sort, 'q' => $searchQuery, 'academic_year' => $request->query('academic_year')]);
        // SECURITY: Visiting Liquidation clears only this user's unread publication notices for this tab.
        if ($user) {
            $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['liquidation'])->update(['read_at' => now()]);
        }
        // Use the management feed only after the role's tab allowlist has accepted the requested tab.
        $view = $workspaceList ? 'workspace.posts.liquidation.index' : 'liquidation.index';

        return view($view, compact('liquidations', 'allowedTabs', 'tab', 'sort', 'sortOptions', 'pendingCount'));
    }

    public function show(Liquidation $liquidation, Request $request, PostEngagement $engagement): View
    {
        try {
            $this->authorize('view', $liquidation);
        } catch (AuthorizationException) {
            abort(404);
        }
        $management = $request->routeIs('workspace.posts.*');
        if ($management) {
            abort_unless($request->user()?->canPostIn('liquidations'), 403);
        } else {
            abort_unless($liquidation->isApproved() && $liquidation->content_status === Liquidation::CONTENT_ACTIVE, 404);
        }
        // SECURITY: Count only after show authorization; the service excludes authors, bots, and nonpublic posts.
        $engagement->recordView($liquidation, $request);
        $engagement->loadLikeState($liquidation, $request);
        $liquidation->load(['author', 'reviewer']);
        $viewerUrl = $request->user() && $request->user()->can('viewFile', $liquidation)
            ? URL::temporarySignedRoute('liquidations.view', now()->addMinutes(5), ['liquidation' => $liquidation])
            : null;

        return view('liquidation.show', compact('liquidation', 'viewerUrl', 'management'));
    }

    public function create(): View
    {
        $this->authorize('create', Liquidation::class);

        return view('liquidation.create');
    }

    public function store(LiquidationRequest $request): RedirectResponse
    {
        // Validate report fields and the configured PDF limit before checking file contents.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('create', Liquidation::class);
        $file = $request->file('file');
        $this->assertPdf($file);
        unset($validated['file']);
        $liquidation = new Liquidation($validated);
        $liquidation->created_by_user_id = $request->user()->user_id;
        $liquidation->content_status = Liquidation::CONTENT_ACTIVE;
        $autoApprove = $request->user()->postsAutoApproved();
        $liquidation->approval_status = $autoApprove ? Liquidation::APPROVAL_APPROVED : Liquidation::APPROVAL_PENDING;
        if ($autoApprove) {
            $liquidation->reviewed_by_user_id = $request->user()->user_id;
            $liquidation->reviewed_at = now();
        }
        [$liquidation->file_path, $liquidation->original_name, $liquidation->file_size] = $this->storePdf($file);
        app(PostFiles::class)->save($liquidation);
        // SECURITY: Pending submissions notify configured reviewers; President posts follow the school toggle.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $liquidation->content_status === Liquidation::CONTENT_ACTIVE) {
            $notifications->published('liquidation', $liquidation, $request->user());
        } else {
            $notifications->submitted('liquidation', $liquidation, $request->user());
        }

        return redirect()->route('workspace.posts.liquidations.show', $liquidation)->with('status', 'The liquidation report was uploaded.');
    }

    public function edit(Liquidation $liquidation): View
    {
        $this->authorize('update', $liquidation);

        return view('liquidation.edit', compact('liquidation'));
    }

    public function update(LiquidationRequest $request, Liquidation $liquidation): RedirectResponse
    {
        // Keep replacement PDFs optional and validate the same editable report fields.
        $validated = $request->safe()->except(['image', 'file']);
        $this->authorize('update', $liquidation);
        $wasApproved = $liquidation->isApproved();
        $wasRejected = $liquidation->isRejected();
        unset($validated['file']);
        $liquidation->fill($validated);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->assertPdf($file);
            [$liquidation->file_path, $liquidation->original_name, $liquidation->file_size] = $this->storePdf($file);
        }
        // SECURITY: Edited rejected reports and configured approved edits return to President review.
        if ($wasRejected || ($wasApproved && $request->user()->isOfficer() && config('school.reset_approval_on_edit', true))) {
            $liquidation->approval_status = Liquidation::APPROVAL_PENDING;
            $liquidation->rejection_reason = null;
            $liquidation->reviewed_by_user_id = null;
            $liquidation->reviewed_at = null;
        }
        app(PostFiles::class)->save($liquidation);
        // SECURITY: Edits returned to pending notify configured reviewers; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('liquidation', $liquidation, $request->user(), true);
        }

        return redirect()->route('workspace.posts.liquidations.show', $liquidation)->with('status', 'The liquidation report was updated.');
    }

    public function archive(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('archive', $liquidation);
        if ($liquidation->isArchived()) {
            return back()->with('status', 'This report is already archived.');
        }
        $liquidation->content_status = Liquidation::CONTENT_ARCHIVED;
        app(PostFiles::class)->save($liquidation);

        return back()->with('status', 'The report was archived.');
    }

    public function restore(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('restore', $liquidation);
        if (! $liquidation->isArchived()) {
            return back()->with('status', 'This report is already active.');
        }
        $liquidation->content_status = Liquidation::CONTENT_ACTIVE;
        app(PostFiles::class)->save($liquidation);

        return back()->with('status', 'The report was restored.');
    }

    public function approve(Request $request, Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('approve', $liquidation);
        if (! $liquidation->isPending()) {
            return back()->with('error', 'Only pending reports can be approved.');
        }
        if (! app(ContentReview::class)->decide($liquidation, $request->user(), true)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The report was approved.');
    }

    public function reject(Request $request, Liquidation $liquidation): RedirectResponse
    {
        // Keep rejection reasons required and within the database limit.
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:255']], [
            'rejection_reason.required' => 'Enter a reason before rejecting this liquidation report.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ]);
        $this->authorize('reject', $liquidation);
        if (! $liquidation->isPending()) {
            return back()->with('error', 'Only pending reports can be rejected.');
        }
        $reason = $validated['rejection_reason'];
        if (! app(ContentReview::class)->decide($liquidation, $request->user(), false, $reason)) {
            return back()->with('error', 'This submission changed or was already reviewed. Reload it before reviewing again.');
        }

        return back()->with('status', 'The report was rejected.');
    }

    public function destroy(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('delete', $liquidation);
        app(PostFiles::class)->delete($liquidation);

        return redirect()->route('workspace.posts.liquidations.index', ['tab' => 'all'])->with('status', 'The report was deleted.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Liquidation::class);
        $validated = $request->validate([
            'liquidation_ids' => ['required', 'array', 'min:1', 'max:100'],
            'liquidation_ids.*' => ['required', 'integer', 'distinct', Rule::exists('liquidations', 'liquidation_id')],
        ]);
        $items = Liquidation::query()->whereIn('liquidation_id', $validated['liquidation_ids'])->get();
        foreach ($items as $item) {
            $this->authorize('archive', $item);
        }
        Liquidation::query()->whereIn('liquidation_id', $items->modelKeys())->update(['content_status' => Liquidation::CONTENT_ARCHIVED, 'updated_at' => now()]);

        return back()->with('status', 'The selected reports were archived.');
    }

    public function viewFile(Liquidation $liquidation, Request $request, PrivatePdfResponse $pdfResponse): StreamedResponse
    {
        // SECURITY: Hide the existence of non-public reports from users without file access.
        try {
            $this->authorize('viewFile', $liquidation);
        } catch (AuthorizationException) {
            abort(404);
        }
        // SECURITY: Signed URLs expire quickly; range fetches retain the same signature and recheck policy.
        abort_unless($request->hasValidSignature(), 404);
        // SECURITY: This best-effort header check blocks ordinary top-level PDF navigation.
        abort_if(strtolower((string) $request->header('Sec-Fetch-Dest')) === 'document', 404);
        if (! $this->isPrivatePdfPath($liquidation->file_path) || ! Storage::disk('local')->exists($liquidation->file_path)) {
            abort(404);
        }

        return $pdfResponse->make(Storage::disk('local')->path($liquidation->file_path), $this->safeOriginalName($liquidation->original_name), $request);
    }

    private function assertPdf(UploadedFile $file): void
    {
        // SECURITY: Extension, finfo MIME, and %PDF header checks reject renamed executables and non-PDF uploads.
        $mime = $file->getMimeType();
        $handle = @fopen($file->getRealPath(), 'rb');
        $header = $handle ? fread($handle, 5) : '';
        if ($handle) {
            fclose($handle);
        }
        if (strtolower($file->getClientOriginalExtension()) !== 'pdf' || $mime !== 'application/pdf' || $header !== '%PDF-') {
            throw ValidationException::withMessages(['file' => 'Choose a genuine PDF file with a .pdf filename.']);
        }
    }

    private function storePdf(UploadedFile $file): array
    {
        // SECURITY: Random server-side names keep client paths out of the private liquidation folder.
        $path = app(PostFiles::class)->store($file, 'liquidation', 'local', true);

        return [$path, $this->safeOriginalName($file->getClientOriginalName()), (int) $file->getSize()];
    }

    private function safeOriginalName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/u', '', $name) ?? '';
        $name = trim($name);
        $name = preg_replace('/\.pdf(?:\.exe|\.com|\.bat|\.cmd)$/i', '', $name) ?? $name;
        $name = preg_replace('/\.pdf$/i', '', $name) ?? $name;
        $name = mb_substr($name !== '' ? $name : 'liquidation', 0, 251);

        return $name.'.pdf';
    }

    private function isPrivatePdfPath(?string $path): bool
    {
        return $path !== null && preg_match('#\Aliquidation/[A-Za-z0-9]{40}\.pdf\z#D', $path) === 1;
    }
}
