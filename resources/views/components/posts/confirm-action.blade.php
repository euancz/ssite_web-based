@props(['id', 'title', 'message', 'formId', 'buttonText'])

{{-- Preserve deliberate confirmation before state-changing post actions. --}}
<dialog id="{{ $id }}" class="reject-modal post-feed-confirm" aria-labelledby="{{ $id }}-title">
    <div class="reject-modal-content">
        <h2 id="{{ $id }}-title">{{ $title }}</h2>
        <p>{{ $message }}</p>
        <div class="reject-modal-actions">
            <button type="button" class="dashboard-button dashboard-button-secondary" data-post-confirm-close="{{ $id }}">Cancel</button>
            <button type="submit" class="dashboard-button" form="{{ $formId }}">{{ $buttonText }}</button>
        </div>
    </div>
</dialog>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-post-confirm-open]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    document.getElementById(trigger.dataset.postConfirmOpen)?.showModal();
                });
            });

            document.querySelectorAll('[data-post-confirm-close]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    document.getElementById(trigger.dataset.postConfirmClose)?.close();
                });
            });
        </script>
    @endpush
@endonce
