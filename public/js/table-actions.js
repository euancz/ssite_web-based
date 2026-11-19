(() => {
    let openPanel;

    document.addEventListener('toggle', (event) => {
        const panel = event.target;
        if (!panel.matches('.table-actions-panel')) return;

        if (event.newState === 'closed') {
            if (openPanel === panel) openPanel = null;
            return;
        }

        openPanel = panel;
        const trigger = panel.previousElementSibling;
        const gap = 6;
        const edge = 8;
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;
        const anchor = trigger.getBoundingClientRect();
        const roomBelow = viewportHeight - anchor.bottom - gap - edge;
        const roomAbove = anchor.top - gap - edge;
        panel.style.maxHeight = '';
        const openAbove = panel.offsetHeight > roomBelow && roomAbove > roomBelow;
        panel.style.maxHeight = `${openAbove ? roomAbove : roomBelow}px`;
        panel.style.left = `${Math.max(edge, Math.min(anchor.right, viewportWidth - edge) - panel.offsetWidth)}px`;
        panel.style.top = `${openAbove ? anchor.top - gap - panel.offsetHeight : anchor.bottom + gap}px`;
    }, true);

    document.addEventListener('click', (event) => {
        const panel = event.target.closest('.table-actions-panel');
        const dialog = document.activeElement?.closest('dialog[open]');
        if (!panel || !dialog) return;

        // Opening a modal dismisses its popover, so return focus to the row's trigger.
        const trigger = panel.previousElementSibling;
        dialog.addEventListener('close', () => trigger.focus(), { once: true });
    });

    // The popover escapes the table's scroll container, so dismiss it when its trigger moves.
    document.addEventListener('scroll', (event) => {
        if (openPanel && !openPanel.contains(event.target)) openPanel.hidePopover();
    }, { capture: true, passive: true });
    window.addEventListener('resize', () => openPanel?.hidePopover());
})();
