export function initFlashToasts(root = typeof document === 'undefined' ? null : document) {
    if (!root || typeof root.querySelectorAll !== 'function') return () => {};

    const messages = [...root.querySelectorAll('[data-flash-toast]')];
    if (!messages.length) return () => {};

    const region = document.createElement('div');
    region.className = 'flash-toast-region';
    region.setAttribute('aria-label', 'Page messages');
    region.tabIndex = -1;

    const entries = messages.map((message) => {
        const placeholder = document.createComment('flash-toast');
        message.replaceWith(placeholder);

        const entry = document.createElement('div');
        entry.className = 'flash-toast-entry';

        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'flash-toast-dismiss';
        dismiss.setAttribute('aria-label', 'Dismiss message');
        dismiss.textContent = 'Dismiss';

        entry.append(message, dismiss);
        region.append(entry);
        dismiss.addEventListener('click', () => {
            const nextDismiss = entry.nextElementSibling?.querySelector('button');
            entry.remove();
            placeholder.remove();
            if (nextDismiss) nextDismiss.focus();
            else if (region.querySelector('.flash-toast-entry')) region.focus();
            else {
                region.remove();
                document.getElementById('main-content')?.focus();
            }
        });

        return { message, entry, placeholder };
    });

    document.body.append(region);
    entries.forEach(({ message }) => message.classList.add('flash-toast'));

    return () => {
        entries.forEach(({ message, entry, placeholder }) => {
            message.classList.remove('flash-toast');
            if (placeholder.isConnected) {
                placeholder.replaceWith(message);
            } else if (entry.isConnected) {
                entry.before(message);
            }
            entry.remove();
        });
        region.remove();
    };
}
