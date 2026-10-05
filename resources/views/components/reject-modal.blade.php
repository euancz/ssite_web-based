@props(['id', 'action' => null])

<dialog id="{{ $id }}" class="reject-modal" aria-labelledby="{{ $id }}-title">
    <div class="reject-modal-content">
        <h2 id="{{ $id }}-title">Reject {{ $action ? 'article' : 'post' }}</h2>
        <p>A rejection reason is required.</p>
        @if ($action)
            {{-- SECURITY: The server validates the reason and rechecks adviser authorization. --}}
            <form method="POST" action="{{ $action }}">
                @csrf
                <label for="{{ $id }}-reason">Reason</label>
                <textarea id="{{ $id }}-reason" name="rejection_reason" rows="4" maxlength="255" required></textarea>
                <div class="reject-modal-actions">
                    <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="{{ $id }}">Cancel</button>
                    <button type="submit" class="dashboard-button">Reject article</button>
                </div>
            </form>
        @else
            {{-- TODO: The adviser review placeholder has no post endpoint until that feature is implemented. --}}
            <label for="{{ $id }}-reason">Reason</label>
            <textarea id="{{ $id }}-reason" rows="4" required></textarea>
            <p class="dashboard-notice">Post review is unavailable until post storage and review actions are implemented.</p>
            <div class="reject-modal-actions">
                <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="{{ $id }}">Cancel</button>
                <button type="button" class="dashboard-button" disabled>Reject post</button>
            </div>
        @endif
    </div>
</dialog>

@once
    @push('scripts')
        <script>
            // Bind once per page so each review row can open or close its shared rejection dialog.
            document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    document.getElementById(trigger.dataset.modalOpen)?.showModal();
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    document.getElementById(trigger.dataset.modalClose)?.close();
                });
            });
        </script>
    @endpush
@endonce
