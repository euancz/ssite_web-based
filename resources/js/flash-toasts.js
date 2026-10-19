export function initFlashToasts(root = typeof document === 'undefined' ? null : document) {
    if (!root || typeof root.querySelectorAll !== 'function') return () => {};

    const messages = [...root.querySelectorAll('[data-flash-toast]')];
    if (!messages.length) return () => {};

    const region = document.createElement('div');
    region.className = 'flash-toast-region';
    region.setAttribute('aria-label', 'Page messages');
    region.tabIndex = -1;

    const entries = messages.map((message) => {
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
            entry.hidden = true;
            if (nextDismiss) nextDismiss.focus();
            else region.focus();
        });

        return { message, entry };
    });

    document.body.append(region);
    entries.forEach(({ message }) => message.classList.add('flash-toast'));

    return () => {
        entries.forEach(({ message, entry }) => {
            message.classList.remove('flash-toast');
            entry.before(message);
            entry.remove();
        });
        region.remove();
    };
}
