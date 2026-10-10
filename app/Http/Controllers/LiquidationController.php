<?php

namespace App\Http\Controllers;

use App\Http\Requests\Liquidation\RejectLiquidationRequest;
use App\Http\Requests\Liquidation\StoreLiquidationRequest;
use App\Http\Requests\Liquidation\UpdateLiquidationRequest;
use App\Models\Liquidation;
use App\Support\PostNotificationRecipients;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * Officer reports start pending; advisers approve or reject with a reason. Only approved AND active
 * reports are public; files stay on the private disk and every file response authorizes before reading.
 * Archive, restore, and delete stay silent; only review and publication transitions send notifications.
 */
class LiquidationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Liquidation::class);
        $user = $request->user();
        $allowedTabs = $user?->isAdviser() ? ['published', 'pending', 'rejected', 'archived', 'all']
            : ($user?->isOfficer() ? ['published', 'mine', 'archived'] : ['published']);
        $tab = $request->query('tab', 'published');
        // SECURITY: Role-specific tab allowlists prevent private workflow states leaking to students.
        if (! in_array($tab, $allowedTabs, true)) $tab = 'published';
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $liquidations = Liquidation::query()->with('author');
        if ($tab === 'published') $liquidations->publiclyVisible();
        elseif ($tab === 'mine' && $user) $liquidations->where('created_by_user_id', $user->user_id);
        elseif ($tab === 'archived') {
            $liquidations->archived();
            if (! $user?->isAdviser()) $liquidations->where('created_by_user_id', $user->user_id);
        } elseif ($tab === 'pending') $liquidations->pending();
        elseif ($tab === 'rejected') $liquidations->rejected();
        $liquidations->when($search !== '', fn ($query) => $query->where('title', 'like', '%' . $search . '%'));
        $liquidations = $liquidations->latest('created_at')->paginate(10)->withQueryString();
        // SECURITY: Visiting Liquidation clears only this user's unread publication notices for this tab.
        if ($user) $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['liquidation'])->update(['read_at' => now()]);
        return view('liquidation.index', compact('liquidations', 'allowedTabs', 'tab', 'search'));
    }

    public function show(Liquidation $liquidation): View
    {
        try { $this->authorize('view', $liquidation); }
        catch (AuthorizationException) { abort(404); }
        $liquidation->load(['author', 'reviewer']);
        return view('liquidation.show', compact('liquidation'));
    }

    public function create(): View { $this->authorize('create', Liquidation::class); return view('liquidation.create'); }

    public function store(StoreLiquidationRequest $request): RedirectResponse
    {
        $this->authorize('create', Liquidation::class);
        $file = $request->file('file');
        $this->assertPdf($file);
        $liquidation = new Liquidation($request->safe()->only(['title', 'description', 'amount', 'report_date']));
        $liquidation->created_by_user_id = $request->user()->user_id;
        $liquidation->content_status = Liquidation::CONTENT_ACTIVE;
        $autoApprove = $request->user()->isAdviser() && config('school.adviser_posts_auto_approved', true);
        $liquidation->approval_status = $autoApprove ? Liquidation::APPROVAL_APPROVED : Liquidation::APPROVAL_PENDING;
        if ($autoApprove) {
            $liquidation->reviewed_by_user_id = $request->user()->user_id;
            $liquidation->reviewed_at = now();
        }
        [$liquidation->file_path, $liquidation->original_name, $liquidation->file_size] = $this->storePdf($file);
        $liquidation->save();
        // Pending submissions go to advisers only; adviser auto-approved posts are already published.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $liquidation->content_status === Liquidation::CONTENT_ACTIVE) $notifications->published('liquidation', $liquidation, $request->user());
        elseif ($request->user()->isOfficer()) $notifications->submitted('liquidation', $liquidation, $request->user());
        return redirect()->route('liquidations.show', $liquidation)->with('status', 'The liquidation report was uploaded.');
    }

    public function edit(Liquidation $liquidation): View
    {
        $this->authorize('update', $liquidation);
        return view('liquidation.edit', compact('liquidation'));
    }

    public function update(UpdateLiquidationRequest $request, Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('update', $liquidation);
        $wasApproved = $liquidation->isApproved();
        $wasRejected = $liquidation->isRejected();
        $oldPath = $liquidation->file_path;
        $liquidation->fill($request->safe()->only(['title', 'description', 'amount', 'report_date']));
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->assertPdf($file);
            [$liquidation->file_path, $liquidation->original_name, $liquidation->file_size] = $this->storePdf($file);
        }
        // SECURITY: Edited rejected reports and configured officer edits return to adviser review.
        if ($wasRejected || ($wasApproved && $request->user()->isOfficer() && config('school.reset_approval_on_edit', true))) {
            $liquidation->approval_status = Liquidation::APPROVAL_PENDING;
            $liquidation->rejection_reason = null;
            $liquidation->reviewed_by_user_id = null;
            $liquidation->reviewed_at = null;
        }
        $liquidation->save();
        // SECURITY: Only officer edits returned to pending trigger a review notice; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('liquidation', $liquidation, $request->user(), true);
        }
        if ($oldPath && $oldPath !== $liquidation->file_path) $this->deletePrivatePath($oldPath);
        return redirect()->route('liquidations.show', $liquidation)->with('status', 'The liquidation report was updated.');
    }

    public function archive(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('archive', $liquidation);
        if ($liquidation->isArchived()) return back()->with('status', 'This report is already archived.');
        $liquidation->content_status = Liquidation::CONTENT_ARCHIVED;
        $liquidation->save();
        return back()->with('status', 'The report was archived.');
    }

    public function restore(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('restore', $liquidation);
        if (! $liquidation->isArchived()) return back()->with('status', 'This report is already active.');
        $liquidation->content_status = Liquidation::CONTENT_ACTIVE;
        $liquidation->save();
        return back()->with('status', 'The report was restored.');
    }

    public function approve(Request $request, Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('approve', $liquidation);
        if (! $liquidation->isPending()) return back()->with('error', 'Only pending reports can be approved.');
        // SECURITY: A conditional state change ensures concurrent clicks can send only one notification set.
        $reviewedAt = now();
        $changed = $liquidation->newQuery()->whereKey($liquidation->getKey())->where('approval_status', Liquidation::APPROVAL_PENDING)
            ->update(['approval_status' => Liquidation::APPROVAL_APPROVED, 'rejection_reason' => null,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => $reviewedAt]);
        if (! $changed) return back()->with('error', 'Only pending reports can be approved.');
        $liquidation->approval_status = Liquidation::APPROVAL_APPROVED;
        app(PostNotificationRecipients::class)->approved('liquidation', $liquidation, $request->user());
        return back()->with('status', 'The report was approved.');
    }

    public function reject(RejectLiquidationRequest $request, Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('reject', $liquidation);
        if (! $liquidation->isPending()) return back()->with('error', 'Only pending reports can be rejected.');
        $reason = $request->validated('rejection_reason');
        $changed = $liquidation->newQuery()->whereKey($liquidation->getKey())->where('approval_status', Liquidation::APPROVAL_PENDING)
            ->update(['approval_status' => Liquidation::APPROVAL_REJECTED, 'rejection_reason' => $reason,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => now()]);
        if (! $changed) return back()->with('error', 'Only pending reports can be rejected.');
        $liquidation->approval_status = Liquidation::APPROVAL_REJECTED;
        $liquidation->rejection_reason = $reason;
        app(PostNotificationRecipients::class)->rejected('liquidation', $liquidation, $request->user(), $reason);
        return back()->with('status', 'The report was rejected.');
    }

    public function destroy(Liquidation $liquidation): RedirectResponse
    {
        $this->authorize('delete', $liquidation);
        $this->deletePrivatePath($liquidation->file_path);
        $liquidation->delete();
        return redirect()->route('liquidations.index', ['tab' => 'all'])->with('status', 'The report was deleted.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Liquidation::class);
        $validated = $request->validate([
            'liquidation_ids' => ['required', 'array', 'min:1', 'max:100'],
            'liquidation_ids.*' => ['required', 'integer', 'distinct', Rule::exists('liquidations', 'liquidation_id')],
        ]);
        $items = Liquidation::query()->whereIn('liquidation_id', $validated['liquidation_ids'])->get();
        foreach ($items as $item) $this->authorize('archive', $item);
        Liquidation::query()->whereIn('liquidation_id', $items->modelKeys())->update(['content_status' => Liquidation::CONTENT_ARCHIVED, 'updated_at' => now()]);
        return back()->with('status', 'The selected reports were archived.');
    }

    public function viewFile(Liquidation $liquidation): StreamedResponse|RedirectResponse
    {
        // SECURITY: Hide the existence of non-public reports from users without file access.
        try { $this->authorize('viewFile', $liquidation); }
        catch (AuthorizationException) { abort(404); }
        return $this->fileResponse($liquidation, true);
    }

    public function download(Liquidation $liquidation): StreamedResponse|RedirectResponse
    {
        // SECURITY: A private report is a 404 to anyone who is not its owner or an adviser.
        try { $this->authorize('download', $liquidation); }
        catch (AuthorizationException) { abort(404); }
        return $this->fileResponse($liquidation, false);
    }

    private function fileResponse(Liquidation $liquidation, bool $inline): StreamedResponse|RedirectResponse
    {
        if (! $this->isPrivatePdfPath($liquidation->file_path) || ! Storage::disk('local')->exists($liquidation->file_path)) {
            return back()->with('error', 'This PDF is currently unavailable. The report record is still available to manage.');
        }
        // SECURITY: The sanitized original label is the download name; the private path is never exposed.
        return Storage::disk('local')->response($liquidation->file_path, $this->safeDownloadName($liquidation->original_name),
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'], $inline ? 'inline' : 'attachment');
    }

    private function assertPdf(UploadedFile $file): void
    {
        // SECURITY: Extension, finfo MIME, and %PDF header checks reject renamed executables and non-PDF uploads.
        $mime = $file->getMimeType();
        $handle = @fopen($file->getRealPath(), 'rb');
        $header = $handle ? fread($handle, 5) : '';
        if ($handle) fclose($handle);
        if (strtolower($file->getClientOriginalExtension()) !== 'pdf' || $mime !== 'application/pdf' || $header !== '%PDF-') {
            throw ValidationException::withMessages(['file' => 'Choose a genuine PDF file with a .pdf filename.']);
        }
    }

    private function storePdf(UploadedFile $file): array
    {
        // SECURITY: Random server-side names keep client paths out of the private liquidation folder.
        $path = $file->storeAs('liquidation', Str::random(40) . '.pdf', 'local');
        return [$path, $this->safeDownloadName($file->getClientOriginalName()), (int) $file->getSize()];
    }

    private function safeDownloadName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/u', '', $name) ?? '';
        $name = trim($name);
        $name = preg_replace('/\.pdf(?:\.exe|\.com|\.bat|\.cmd)$/i', '', $name) ?? $name;
        $name = preg_replace('/\.pdf$/i', '', $name) ?? $name;
        $name = mb_substr($name !== '' ? $name : 'liquidation', 0, 251);
        return $name . '.pdf';
    }

    private function deletePrivatePath(?string $path): void
    {
        // SECURITY: Deletion is restricted to this module's own private storage directory.
        if ($this->isPrivatePdfPath($path)) Storage::disk('local')->delete($path);
    }

    private function isPrivatePdfPath(?string $path): bool
    {
        return $path !== null && preg_match('#\Aliquidation/[A-Za-z0-9]{40}\.pdf\z#D', $path) === 1;
    }
}
