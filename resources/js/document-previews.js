import * as pdfjs from 'pdfjs-dist/build/pdf.mjs';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

export function initDocumentPreviews() {
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            observer.unobserve(entry.target);
            renderPreview(entry.target);
        }
    }, { rootMargin: '150px' });
    document.querySelectorAll('[data-document-preview]').forEach((preview) => observer.observe(preview));
}

async function renderPreview(preview) {
    // Signed URLs retain the existing authentication and file-policy checks.
    const task = pdfjs.getDocument({ url: preview.dataset.documentPreview, withCredentials: true });
    try {
        const pdf = await task.promise;
        const page = await pdf.getPage(1);
        const canvas = preview.querySelector('canvas');
        const base = page.getViewport({ scale: 1 });
        const viewport = page.getViewport({ scale: (preview.clientWidth * Math.min(devicePixelRatio || 1, 2)) / base.width });
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        canvas.hidden = false;
        preview.querySelector('.document-preview-fallback').hidden = true;
    } catch {
        // Expired links or missing files keep the accessible details link and fallback.
    } finally {
        await task.destroy();
    }
}
