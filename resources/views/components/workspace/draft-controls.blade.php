@props(['module', 'draft' => null])

@if (session('status') || $draft)
<div class="workspace-draft-controls">
    @if (session('status'))<p class="dashboard-success" role="status">{{ session('status') }}</p>@endif
    @if ($draft)
        <input type="hidden" name="draft_id" value="{{ $draft->getKey() }}">
        <input type="hidden" name="draft_version" value="{{ old('draft_version', $draft->lock_version) }}">
        <p><strong>Private draft</strong> &middot; Saved {{ $draft->updated_at?->format('M j, Y g:i A') }}. Only you can access it.</p>
        @if ($draft->attachment())
            <p class="profile-field-help">Saved attachment: {{ $draft->attachment()['original_name'] ?? 'Selected file' }}</p>
        @endif
    @endif
</div>
@endif
<button type="submit" class="dashboard-button dashboard-button-secondary" formaction="{{ route('workspace.drafts.save', $module) }}" formnovalidate data-save-draft>Save draft</button>
