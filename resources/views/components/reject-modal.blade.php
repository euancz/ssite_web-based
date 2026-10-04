@props(['id'])

<dialog id="{{ $id }}" class="reject-modal" aria-labelledby="{{ $id }}-title">
    <div class="reject-modal-content">
        <h2 id="{{ $id }}-title">Reject post</h2>
        <p>A rejection reason is required.</p>
        <label for="{{ $id }}-reason">Reason</label>
        <textarea id="{{ $id }}-reason" rows="4" required></textarea>
        <p class="dashboard-notice">
            Post review is unavailable until post storage and review actions are implemented.
        </p>
        <div class="reject-modal-actions">
            <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="{{ $id }}">
                Cancel
            </button>
            <button type="button" class="dashboard-button" disabled>
                Reject post
            </button>
        </div>
    </div>
</dialog>

@once
    @push('scripts')
        <script>
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
