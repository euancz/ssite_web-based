const triggers = document.querySelectorAll('[data-document-selection]');
let viewerModule;

for (const trigger of triggers) {
    const dialog = document.getElementById(trigger.dataset.documentSelection);
    if (!dialog) continue;
    const viewer = dialog.querySelector('[data-selection-viewer]');
    const expand = dialog.querySelector('[data-selection-expand]');
    const updateBackdrop = () => {
        if (!dialog.open) return;
        const footerTop = document.querySelector('.site-footer')?.getBoundingClientRect().top ?? innerHeight;
        const headerBottom = document.querySelector('.site-header')?.getBoundingClientRect().bottom ?? 0;
        dialog.style.setProperty('--selection-header-inset', `${Math.max(0, headerBottom)}px`);
        dialog.style.setProperty('--selection-footer-inset', `${Math.max(0, innerHeight - footerTop)}px`);
    };
    window.addEventListener('resize', updateBackdrop);
    const layoutObserver = new ResizeObserver(updateBackdrop);
    for (const element of document.querySelectorAll('.site-header, .documents-page, .transparency-page, .site-footer')) layoutObserver.observe(element);
    document.fonts.ready.then(updateBackdrop);
    const resetExpanded = () => {
        dialog.classList.remove('is-expanded');
        expand?.setAttribute('aria-pressed', 'false');
        expand?.setAttribute('aria-label', 'Expand document preview');
    };

    trigger.addEventListener('click', async (event) => {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        dialog.showModal();
        updateBackdrop();
        dialog.querySelector('.document-selection-heading h2').focus({ preventScroll: true });
        if (!viewer || viewer.dataset.initialized === 'true') return;
        viewer.dataset.initialized = 'true';
        try {
            viewerModule ??= import('./document-pdf-viewer');
            const { initPrivatePdfViewer } = await viewerModule;
            if (!dialog.open) {
                viewer.dataset.initialized = 'false';
                return;
            }
            initPrivatePdfViewer(viewer);
        } catch {
            viewerModule = null;
            viewer.dataset.initialized = 'false';
            viewer.querySelector('[data-pdf-status]').textContent = 'The preview could not load. Close and reopen this document to try again.';
        }
    });
    dialog.querySelector('[data-selection-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        resetExpanded();
        trigger.focus({ preventScroll: true });
    });
    expand?.addEventListener('click', () => {
        const expanded = dialog.classList.toggle('is-expanded');
        expand.setAttribute('aria-pressed', String(expanded));
        expand.setAttribute('aria-label', expanded ? 'Restore document preview size' : 'Expand document preview');
    });
}
