<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Rules\MaxWords;
use App\Support\PostNotificationRecipients;
use App\Support\PostEngagement;
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

/* Position-based submissions start pending; the President reviews and may publish directly.
 * PDF bytes stay private behind authorized routes; archive and restore preserve review state.
 */
class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, PostEngagement $engagement): View
    {
        $this->authorize('viewAny', Document::class);
        $user = $request->user();
        $allowedTabs = $user?->canReview() ? ['published', 'pending', 'rejected', 'archived', 'all']
            : ($user?->canPostIn('documents') ? ['published', 'mine', 'archived'] : ['published']);
        // SECURITY: Pending totals are queried only for review-capable users and never reach public or student views.
        $pendingCount = $user?->canReview() ? Document::pending()->count() : null;
        $tab = $request->query('tab', 'published');
        // SECURITY: A tab whitelist prevents guests and students from requesting private workflow rows.
        if (! in_array($tab, $allowedTabs, true)) $tab = 'published';

        $documents = Document::query()->with('author');
        if ($tab === 'published') $documents->publiclyVisible();
        elseif ($tab === 'mine' && $user) $documents->where('created_by_user_id', $user->user_id);
        elseif ($tab === 'archived') {
            $documents->archived();
            if (! $user?->isPresident()) $documents->where('created_by_user_id', $user->user_id);
        } elseif ($tab === 'pending') $documents->pending();
        elseif ($tab === 'rejected') $documents->rejected();

        // SECURITY: Category choices are derived from the already visibility-scoped query for this tab.
        $categories = (clone $documents)->whereNotNull('category')->where('category', '<>', '')
            ->distinct()->orderBy('category')->pluck('category')->all();
        $requestedCategory = $request->query('category');
        $category = is_string($requestedCategory) && in_array($requestedCategory, $categories, true)
            ? $requestedCategory
            : '';
        $categoryOptions = ['' => 'All categories'];
        foreach ($categories as $option) {
            $categoryOptions[$option] = $option;
        }
        if ($category !== '') $documents->where('category', $category);

        // SECURITY: Engagement queries are limited to Published and require both the feature flag and schema.
        $viewCountsEnabled = $tab === 'published' && $engagement->viewCountsAvailable($documents->getModel());
        $likesEnabled = $tab === 'published' && $engagement->likesAvailable();
        if ($likesEnabled) {
            $documents->withCount('likes');
            if ($user) $documents->withCount(['likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $user->user_id)]);
        }

        // SECURITY: Sort keys select fixed column/direction pairs; request text is never an SQL identifier.
        $sortMap = ['recent' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc']];
        if ($viewCountsEnabled) $sortMap['viewed'] = ['view_count', 'desc'];
        if ($likesEnabled) $sortMap['liked'] = ['likes_count', 'desc'];
        $sort = $request->query('sort');
        $sort = is_string($sort) && array_key_exists($sort, $sortMap) ? $sort : 'recent';
        $sortOptions = ['recent' => 'Most recent', 'oldest' => 'Oldest', 'title' => 'Title (A to Z)'];
        if ($viewCountsEnabled) $sortOptions['viewed'] = 'Most viewed';
        if ($likesEnabled) $sortOptions['liked'] = 'Most liked';
        [$sortColumn, $sortDirection] = $sortMap[$sort];
        $documents->orderBy($sortColumn, $sortDirection);
        if (in_array($sort, ['viewed', 'liked'], true)) $documents->orderBy('created_at', 'desc');
        $documents = $documents->orderBy('document_id', 'asc')
            ->paginate(10)
            ->appends(['tab' => $tab, 'sort' => $sort, 'category' => $category]);
        // SECURITY: Visiting Documents clears only this user's unread publication notices for this tab.
        if ($user) $user->unreadNotifications()->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = ?", ['published'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) = ?", ['document'])->update(['read_at' => now()]);

        return view('documents.index', compact('documents', 'allowedTabs', 'tab', 'sort', 'sortOptions', 'category', 'categoryOptions', 'pendingCount'));
    }

    public function show(Document $document, Request $request, PostEngagement $engagement): View
    {
        try { $this->authorize('view', $document); }
        catch (AuthorizationException) { abort(404); }
        // SECURITY: Count only after show authorization; the service excludes authors, bots, and nonpublic posts.
        $engagement->recordView($document, $request);
        $document->load(['author', 'reviewer']);
        return view('documents.show', compact('document'));
    }

    public function create(): View { $this->authorize('create', Document::class); return view('documents.create'); }

    public function store(Request $request): RedirectResponse
    {
        // Validate the metadata and configured PDF size before checking the file contents.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', new MaxWords],
            'category' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:' . config('school.max_pdf_size_kb', 10240)],
        ], [
            'title.required' => 'Enter a document title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'file.required' => 'Choose a PDF to upload.',
            'file.mimes' => 'Choose a valid PDF document.',
            'file.max' => 'The PDF exceeds the configured upload size limit.',
        ]);
        $this->authorize('create', Document::class);
        $file = $request->file('file');
        $this->assertPdf($file);
        unset($validated['file']);
        $document = new Document($validated);
        $document->created_by_user_id = $request->user()->user_id;
        $document->content_status = Document::CONTENT_ACTIVE;
        $autoApprove = $request->user()->postsAutoApproved();
        $document->approval_status = $autoApprove ? Document::APPROVAL_APPROVED : Document::APPROVAL_PENDING;
        if ($autoApprove) {
            $document->reviewed_by_user_id = $request->user()->user_id;
            $document->reviewed_at = now();
        }
        [$document->file_path, $document->original_name, $document->file_size] = $this->storePdf($file);
        $document->save();
        // SECURITY: Pending submissions notify configured reviewers; President posts follow the school toggle.
        $notifications = app(PostNotificationRecipients::class);
        if ($autoApprove && $document->content_status === Document::CONTENT_ACTIVE) $notifications->published('document', $document, $request->user());
        else $notifications->submitted('document', $document, $request->user());
        return redirect()->route('documents.show', $document)->with('status', 'The document was uploaded.');
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);
        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        // Keep a replacement PDF optional while validating the same editable metadata.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', new MaxWords],
            'category' => ['nullable', 'string', 'max:100'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:' . config('school.max_pdf_size_kb', 10240)],
        ], [
            'title.required' => 'Enter a document title.',
            'title.max' => 'The title may not be longer than 200 characters.',
            'category.max' => 'The category may not be longer than 100 characters.',
            'file.mimes' => 'Choose a valid PDF document.',
            'file.max' => 'The PDF exceeds the configured upload size limit.',
        ]);
        $this->authorize('update', $document);
        $wasApproved = $document->isApproved();
        $wasRejected = $document->isRejected();
        $oldPath = $document->file_path;
        unset($validated['file']);
        $document->fill($validated);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->assertPdf($file);
            [$document->file_path, $document->original_name, $document->file_size] = $this->storePdf($file);
        }
        // SECURITY: A configured edit to approved content returns it to President review.
        if ($wasRejected || ($wasApproved && $request->user()->isOfficer() && config('school.reset_approval_on_edit', true))) {
            $document->approval_status = Document::APPROVAL_PENDING;
            $document->rejection_reason = null;
            $document->reviewed_by_user_id = null;
            $document->reviewed_at = null;
        }
        $document->save();
        // SECURITY: Edits returned to pending notify configured reviewers; failures never block saving.
        if ($request->user()->isOfficer() && ($wasRejected || ($wasApproved && config('school.reset_approval_on_edit', true)))) {
            app(PostNotificationRecipients::class)->submitted('document', $document, $request->user(), true);
        }
        if ($oldPath && $oldPath !== $document->file_path) $this->deletePrivatePath($oldPath);
        return redirect()->route('documents.show', $document)->with('status', 'The document was updated.');
    }

    public function archive(Document $document): RedirectResponse
    {
        $this->authorize('archive', $document);
        if ($document->isArchived()) return back()->with('status', 'This document is already archived.');
        $document->content_status = Document::CONTENT_ARCHIVED;
        $document->save();
        return back()->with('status', 'The document was archived.');
    }

    public function restore(Document $document): RedirectResponse
    {
        $this->authorize('restore', $document);
        if (! $document->isArchived()) return back()->with('status', 'This document is already active.');
        $document->content_status = Document::CONTENT_ACTIVE;
        $document->save();
        return back()->with('status', 'The document was restored.');
    }

    public function approve(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);
        if (! $document->isPending()) return back()->with('error', 'Only pending documents can be approved.');
        // SECURITY: A conditional state change ensures concurrent clicks can send only one notification set.
        $reviewedAt = now();
        $changed = $document->newQuery()->whereKey($document->getKey())->where('approval_status', Document::APPROVAL_PENDING)
            ->update(['approval_status' => Document::APPROVAL_APPROVED, 'rejection_reason' => null,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => $reviewedAt]);
        if (! $changed) return back()->with('error', 'Only pending documents can be approved.');
        $document->approval_status = Document::APPROVAL_APPROVED;
        app(PostNotificationRecipients::class)->approved('document', $document, $request->user());
        return back()->with('status', 'The document was approved.');
    }

    public function reject(Request $request, Document $document): RedirectResponse
    {
        // Keep rejection reasons required and within the database limit.
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:255']], [
            'rejection_reason.required' => 'Enter a reason before rejecting this document.',
            'rejection_reason.max' => 'The rejection reason may not be longer than 255 characters.',
        ]);
        $this->authorize('reject', $document);
        if (! $document->isPending()) return back()->with('error', 'Only pending documents can be rejected.');
        $reason = $validated['rejection_reason'];
        $changed = $document->newQuery()->whereKey($document->getKey())->where('approval_status', Document::APPROVAL_PENDING)
            ->update(['approval_status' => Document::APPROVAL_REJECTED, 'rejection_reason' => $reason,
                'reviewed_by_user_id' => $request->user()->user_id, 'reviewed_at' => now()]);
        if (! $changed) return back()->with('error', 'Only pending documents can be rejected.');
        $document->approval_status = Document::APPROVAL_REJECTED;
        $document->rejection_reason = $reason;
        app(PostNotificationRecipients::class)->rejected('document', $document, $request->user(), $reason);
        return back()->with('status', 'The document was rejected.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        $this->deletePrivatePath($document->file_path);
        // SECURITY: Remove module-scoped likes before permanently deleting their parent post.
        app(PostEngagement::class)->deleteLikesForPost($document);
        $document->delete();
        return redirect()->route('documents.index', ['tab' => 'all'])->with('status', 'The document was deleted.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->authorize('bulkArchive', Document::class);
        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1', 'max:100'],
            'document_ids.*' => ['required', 'integer', 'distinct', Rule::exists('documents', 'document_id')],
        ]);
        $documents = Document::query()->whereIn('document_id', $validated['document_ids'])->get();
        foreach ($documents as $document) $this->authorize('archive', $document);
        Document::query()->whereIn('document_id', $documents->modelKeys())->update(['content_status' => Document::CONTENT_ARCHIVED, 'updated_at' => now()]);
        return back()->with('status', 'The selected documents were archived.');
    }

    public function viewFile(Document $document): StreamedResponse|RedirectResponse
    {
        // SECURITY: Hide the existence of a non-public document from users without file access.
        try { $this->authorize('viewFile', $document); }
        catch (AuthorizationException) { abort(404); }
        return $this->fileResponse($document, true);
    }

    public function download(Document $document): StreamedResponse|RedirectResponse
    {
        // SECURITY: A private document is a 404 to anyone other than its owner or the President.
        try { $this->authorize('download', $document); }
        catch (AuthorizationException) { abort(404); }
        return $this->fileResponse($document, false);
    }

    private function fileResponse(Document $document, bool $inline): StreamedResponse|RedirectResponse
    {
        if (! $this->isPrivatePdfPath($document->file_path) || ! Storage::disk('local')->exists($document->file_path)) {
            return back()->with('error', 'This PDF is currently unavailable. The document record is still available to manage.');
        }
        // SECURITY: Download names come from a cleaned client label, never from the private storage path.
        $name = $this->safeDownloadName($document->original_name);
        $headers = ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'];
        return Storage::disk('local')->response($document->file_path, $name, $headers, $inline ? 'inline' : 'attachment');
    }

    private function assertPdf(UploadedFile $file): void
    {
        // SECURITY: Extension, finfo-detected MIME, and the PDF header must all match; client MIME is not trusted.
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
        // SECURITY: Random server filenames prevent user-controlled paths and collisions on the private disk.
        $path = $file->storeAs('documents', Str::random(40) . '.pdf', 'local');
        return [$path, $this->safeDownloadName($file->getClientOriginalName()), (int) $file->getSize()];
    }

    private function safeDownloadName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/u', '', $name) ?? '';
        $name = trim($name);
        $name = preg_replace('/\.pdf(?:\.exe|\.com|\.bat|\.cmd)$/i', '', $name) ?? $name;
        $name = preg_replace('/\.pdf$/i', '', $name) ?? $name;
        $name = mb_substr($name !== '' ? $name : 'document', 0, 251);
        return $name . '.pdf';
    }

    private function deletePrivatePath(?string $path): void
    {
        // SECURITY: Only paths within this module's private folder may be removed.
        if ($this->isPrivatePdfPath($path)) Storage::disk('local')->delete($path);
    }

    private function isPrivatePdfPath(?string $path): bool
    {
        return $path !== null && preg_match('#\Adocuments/[A-Za-z0-9]{40}\.pdf\z#D', $path) === 1;
    }
}
