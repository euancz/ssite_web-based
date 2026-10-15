{{-- The default subject preserves Article wording when other modules pass their own label. --}}
@props(['id', 'action', 'subject' => 'article'])

<dialog id="{{ $id }}" class="reject-modal" aria-labelledby="{{ $id }}-title">
    <div class="reject-modal-content">
        <h2 id="{{ $id }}-title">Reject {{ $action ? $subject : 'post' }}</h2>
        <p>A rejection reason is required.</p>
        {{-- SECURITY: The server validates the reason and rechecks President authorization. --}}
        <form method="POST" action="{{ $action }}">
            @csrf
            <label for="{{ $id }}-reason">Reason</label>
            <textarea id="{{ $id }}-reason" name="rejection_reason" rows="4" maxlength="255" required></textarea>
            <div class="reject-modal-actions">
                <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="{{ $id }}">Cancel</button>
                <button type="submit" class="dashboard-button">Reject {{ $subject }}</button>
            </div>
        </form>
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
