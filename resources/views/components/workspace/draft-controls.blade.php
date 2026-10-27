@props(['module', 'draft' => null])

<div class="workspace-draft-controls">
    @if (session('status'))<p class="dashboard-success" role="status">{{ session('status') }}</p>@endif
    @if ($draft)
        <input type="hidden" name="draft_id" value="{{ $draft->getKey() }}">
        <input type="hidden" name="draft_version" value="{{ old('draft_version', $draft->lock_version) }}">
        <p><strong>Private draft</strong> &middot; Saved {{ $draft->updated_at?->format('M j, Y g:i A') }}. Only you can access it.</p>
    @endif
    <p class="profile-field-help" id="draft-save-note">Save text and details to finish later. Selected images and PDFs are not saved in drafts; attach them when submitting.</p>
    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button dashboard-button-secondary" formaction="{{ route('workspace.drafts.save', $module) }}" formnovalidate aria-describedby="draft-save-note" data-save-draft>Save draft</button>
        <a class="dashboard-text-button" href="{{ route('workspace.drafts.index') }}">My drafts</a>
    </div>
</div>
