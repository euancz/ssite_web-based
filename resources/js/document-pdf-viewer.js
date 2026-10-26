import * as pdfjs from 'pdfjs-dist/build/pdf.mjs';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.mjs?url';
import '../css/document-pdf-viewer.css';

// Both modules use the same canvas-only reader, and each request keeps the signed URL policy checks.
pdfjs.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

export function initPrivatePdfViewer(viewer) {
    if (!viewer) return;

    const canvas = viewer.querySelector('[data-pdf-canvas]');
    const stage = viewer.querySelector('[data-pdf-stage]');
    const status = viewer.querySelector('[data-pdf-status]');
    const pageStatus = viewer.querySelector('[data-pdf-page-status]');
    const zoomStatus = viewer.querySelector('[data-pdf-zoom-status]');
    const previous = viewer.querySelector('[data-pdf-previous]');
    const next = viewer.querySelector('[data-pdf-next]');
    const zoomOut = viewer.querySelector('[data-pdf-zoom-out]');
    const zoomIn = viewer.querySelector('[data-pdf-zoom-in]');
    const fit = viewer.querySelector('[data-pdf-fit]');
    const fullscreen = viewer.querySelector('[data-pdf-fullscreen]');
    const retry = viewer.querySelector('[data-pdf-retry]');
    const context = canvas?.getContext('2d', { alpha: false });

    if (!canvas || !stage || !status || !pageStatus || !context || !viewer.dataset.pdfUrl) {
        if (status) {
            status.textContent = 'This document is not available to view.';
            status.setAttribute('role', 'alert');
        }
        return;
    }

    let pdfDocument = null;
    let loadingTask = null;
    let pageNumber = 1;
    let zoom = 1;
    let renderTask = null;
    let renderSequence = 0;
    let resizeTimer = null;
    let lastStageWidth = stage.clientWidth;
    let lastProgressNotice = -1;

    // These canvas-only interaction speed bumps discourage casual copying; they do not protect PDF content.
    canvas.draggable = false;
    canvas.addEventListener('contextmenu', (event) => event.preventDefault());
    canvas.addEventListener('dragstart', (event) => event.preventDefault());

    // Printing intentionally reports that PDF pages are omitted; it does not print or block the source file.
    let printNotice = null;
    const showPrintNotice = () => {
        if (printNotice) return;
        printNotice = document.createElement('p');
        printNotice.className = 'document-pdf-print-notice';
        printNotice.setAttribute('role', 'status');
        printNotice.textContent = 'Printing this page does not include the PDF pages. Use the on-screen viewer to read the document.';
        viewer.prepend(printNotice);
    };
    const removePrintNotice = () => {
        printNotice?.remove();
        printNotice = null;
    };
    window.addEventListener('beforeprint', showPrintNotice);
    window.addEventListener('afterprint', removePrintNotice);

    const updatePageStatus = () => {
        pageStatus.textContent = pdfDocument ? `Page ${pageNumber} of ${pdfDocument.numPages}` : 'Loading page…';
        if (zoomStatus) zoomStatus.textContent = `${Math.round(zoom * 100)}%`;
        if (previous) previous.disabled = !pdfDocument || pageNumber <= 1;
        if (next) next.disabled = !pdfDocument || pageNumber >= pdfDocument.numPages;
        if (zoomOut) zoomOut.disabled = !pdfDocument || zoom <= 0.75;
        if (zoomIn) zoomIn.disabled = !pdfDocument || zoom >= 2.5;
        if (fit) fit.disabled = !pdfDocument || zoom === 1;
        if (canvas) canvas.setAttribute('aria-label', `PDF page ${pageNumber}`);
    };

    const setBusy = (busy) => {
        if (busy) [previous, next, zoomOut, zoomIn, fit, fullscreen].forEach((button) => {
            if (button) button.disabled = true;
        });
        else {
            updatePageStatus();
            if (fullscreen) fullscreen.disabled = false;
        }
    };

    const showError = (error) => {
        if (error?.name === 'RenderingCancelledException') return;
        const expired = [401, 403, 404, 419].includes(Number(error?.status));
        status.textContent = expired
            ? 'This viewing link expired or access is no longer available. Reload this page for a fresh link.'
            : 'Could not load this PDF. Check your connection, then reload this page to try again.';
        status.setAttribute('role', 'alert');
        retry.hidden = false;
        setBusy(false);
    };

    const setFullscreen = (active) => {
        viewer.classList.toggle('is-fullscreen', active);
        if (fullscreen) {
            fullscreen.textContent = active ? 'Exit full screen' : 'Full screen';
            fullscreen.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        if (!active) fullscreen?.focus();
    };

    const renderPage = async () => {
        if (!pdfDocument) return;
        const sequence = ++renderSequence;
        if (renderTask) {
            renderTask.cancel();
            try { await renderTask.promise; } catch { /* Replacing the active page cancels its render task. */ }
        }
        if (sequence !== renderSequence) return;

        setBusy(true);
        delete status.dataset.ready;
        status.textContent = 'Rendering page…';
        status.setAttribute('role', 'status');
        retry.hidden = true;
        const page = await pdfDocument.getPage(pageNumber);
        const unitViewport = page.getViewport({ scale: 1 });
        const stageStyle = window.getComputedStyle(stage);
        const horizontalPadding = parseFloat(stageStyle.paddingLeft) + parseFloat(stageStyle.paddingRight);
        const availableWidth = Math.max(120, stage.clientWidth - horizontalPadding);
        const fitScale = availableWidth / unitViewport.width;
        const viewport = page.getViewport({ scale: fitScale * zoom });
        const requestedRatio = Math.min(window.devicePixelRatio || 1, 2);
        const pixelRatio = Math.min(
            requestedRatio,
            4095.5 / viewport.width,
            4095.5 / viewport.height,
            Math.sqrt(15_999_000 / (viewport.width * viewport.height)),
        );

        // One canvas is retained at a time; cap its backing store for phone memory and canvas limits.
        canvas.width = Math.max(1, Math.ceil(viewport.width * pixelRatio));
        canvas.height = Math.max(1, Math.ceil(viewport.height * pixelRatio));
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        renderTask = page.render({
            canvasContext: context,
            viewport,
            ...(pixelRatio !== 1 ? { transform: [pixelRatio, 0, 0, pixelRatio, 0, 0] } : {}),
        });

        try {
            await renderTask.promise;
            if (sequence !== renderSequence) return;
            renderTask = null;
            status.textContent = 'Document ready.';
            status.dataset.ready = 'true';
            status.setAttribute('role', 'status');
            updatePageStatus();
            setBusy(false);
        } catch (error) {
            if (sequence === renderSequence) showError(error);
        }
    };

    const loadDocument = async () => {
        setBusy(true);
        status.textContent = 'Loading document…';
        status.setAttribute('role', 'status');
        retry.hidden = true;
        lastProgressNotice = -1;
        try {
            loadingTask = pdfjs.getDocument({ url: viewer.dataset.pdfUrl, rangeChunkSize: 65536 });
            loadingTask.onProgress = ({ loaded, total }) => {
                if (!total) return;
                const progress = Math.min(100, Math.floor((loaded / total) * 100));
                const notice = Math.floor(progress / 10);
                if (notice === lastProgressNotice) return;
                lastProgressNotice = notice;
                status.textContent = `Loading document… ${progress}%`;
            };
            pdfDocument = await loadingTask.promise;
            updatePageStatus();
            await renderPage();
        } catch (error) {
            showError(error);
        }
    };

    const changePage = (delta) => {
        if (!pdfDocument) return;
        const nextPage = Math.max(1, Math.min(pdfDocument.numPages, pageNumber + delta));
        if (nextPage === pageNumber) return;
        pageNumber = nextPage;
        updatePageStatus();
        renderPage().catch(showError);
    };

    const changeZoom = (delta) => {
        zoom = Math.max(0.75, Math.min(2.5, Math.round((zoom + delta) * 100) / 100));
        updatePageStatus();
        renderPage().catch(showError);
    };

    const fitWidth = () => {
        zoom = 1;
        updatePageStatus();
        renderPage().catch(showError);
    };

    const scheduleFitRender = () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            const width = stage.clientWidth;
            if (width === lastStageWidth) return;
            lastStageWidth = width;
            if (zoom === 1 && pdfDocument) renderPage().catch(showError);
        }, 140);
    };

    previous?.addEventListener('click', () => changePage(-1));
    next?.addEventListener('click', () => changePage(1));
    zoomOut?.addEventListener('click', () => changeZoom(-0.25));
    zoomIn?.addEventListener('click', () => changeZoom(0.25));
    fit?.addEventListener('click', fitWidth);
    retry?.addEventListener('click', () => window.location.reload());
    fullscreen?.addEventListener('click', async () => {
        const active = viewer.classList.contains('is-fullscreen');
        if (active) {
            if (document.fullscreenElement === viewer && document.exitFullscreen) {
                try { await document.exitFullscreen(); } catch { setFullscreen(false); }
            } else setFullscreen(false);
            return;
        }
        if (viewer.requestFullscreen) {
            try {
                await viewer.requestFullscreen();
                setFullscreen(true);
                return;
            } catch { /* Use the fixed-position fallback when native fullscreen is unavailable. */ }
        }
        setFullscreen(true);
    });

    viewer.addEventListener('keydown', (event) => {
        if (event.altKey || event.ctrlKey || event.metaKey || event.target.isContentEditable
            || /^(INPUT|TEXTAREA|SELECT)$/.test(event.target.tagName)) return;
        if (event.key === 'ArrowLeft') { event.preventDefault(); changePage(-1); }
        else if (event.key === 'ArrowRight') { event.preventDefault(); changePage(1); }
        else if (event.key === '+' || event.key === '=') { event.preventDefault(); changeZoom(0.25); }
        else if (event.key === '-') { event.preventDefault(); changeZoom(-0.25); }
        else if (event.key === '0') { event.preventDefault(); fitWidth(); }
        else if (event.key === 'Escape' && viewer.classList.contains('is-fullscreen')) {
            if (document.fullscreenElement === viewer && document.exitFullscreen) document.exitFullscreen().catch(() => setFullscreen(false));
            else setFullscreen(false);
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (document.fullscreenElement === viewer) setFullscreen(true);
        else if (viewer.classList.contains('is-fullscreen')) setFullscreen(false);
    });

    window.addEventListener('resize', scheduleFitRender, { passive: true });
    window.addEventListener('orientationchange', scheduleFitRender, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(scheduleFitRender).observe(viewer);

    updatePageStatus();
    loadDocument();
}
