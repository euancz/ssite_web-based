import './bootstrap';
import { initMotion } from './motion';
import { initFlashToasts } from './flash-toasts';

initMotion();
initFlashToasts();

if (document.querySelector('[data-document-selection]')) {
    import('./document-selection');
}

if (document.querySelector('[data-document-preview]')) {
    import('./document-previews').then(({ initDocumentPreviews }) => initDocumentPreviews()).catch(() => {});
}

// Load PDF.js only after an authorized user opens the viewer dialog.
const pdfModalTriggers = [...document.querySelectorAll('[data-pdf-modal-open]')];
if (pdfModalTriggers.length) {
    let pdfViewerModule = null;

    pdfModalTriggers.forEach((trigger) => {
        const dialog = document.getElementById(trigger.dataset.pdfModalOpen);
        const viewer = dialog?.querySelector('[data-pdf-viewer]');
        if (!dialog || !viewer) return;

        trigger.addEventListener('click', async () => {
            if (!dialog.open) dialog.showModal();
            viewer.focus();
            if (viewer.dataset.pdfInitialized === 'true') return;
            viewer.dataset.pdfInitialized = 'true';

            try {
                pdfViewerModule ??= import('./document-pdf-viewer');
                const { initPrivatePdfViewer } = await pdfViewerModule;
                if (!dialog.open) {
                    viewer.dataset.pdfInitialized = 'false';
                    return;
                }
                initPrivatePdfViewer(viewer);
            } catch {
                pdfViewerModule = null;
                viewer.dataset.pdfInitialized = 'false';
                const status = viewer.querySelector('[data-pdf-status]');
                const retry = viewer.querySelector('[data-pdf-retry]');
                if (status) {
                    status.textContent = 'The document viewer could not start. Reload this page to try again.';
                    status.setAttribute('role', 'alert');
                }
                if (retry) retry.hidden = false;
            }
        });

        dialog.querySelector('[data-pdf-dialog-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', () => {
            viewer.classList.remove('is-fullscreen');
            if (document.fullscreenElement === viewer) document.exitFullscreen?.().catch(() => {});
            const fullscreen = viewer.querySelector('[data-pdf-fullscreen]');
            fullscreen?.setAttribute('aria-pressed', 'false');
            if (fullscreen) fullscreen.textContent = 'Full screen';
        });
    });
}
