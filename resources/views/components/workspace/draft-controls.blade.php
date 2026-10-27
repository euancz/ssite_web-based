@props(['module', 'draft' => null])

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
    <p class="profile-field-help" id="draft-save-note">Save text, details, and one selected image or PDF to finish later. Selecting a replacement updates the saved attachment when you save.</p>
    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button dashboard-button-secondary" formaction="{{ route('workspace.drafts.save', $module) }}" formnovalidate aria-describedby="draft-save-note" data-save-draft>Save draft</button>
        <a class="dashboard-text-button" href="{{ route('workspace.drafts.index') }}">My drafts</a>
    </div>
</div>
