import { protectUnsavedContent } from './unsaved-content';
import { initContentUploads } from './content-uploads';

// Progressive enhancement: every form remains usable with ordinary HTML submission.
export function initContentForms() {
    document.querySelector('[data-form-errors]')?.focus();
    document.querySelectorAll('[data-content-form]').forEach((form) => {
        protectUnsavedContent(form);
        initContentUploads(form);
        const livePreview = form.querySelector('[data-content-preview]');
        const body = form.querySelector('textarea[name="content"], textarea[name="description"]');
        const counter = body ? document.createElement('span') : null;
        if (counter) {
            counter.id = `${body.id}-word-count`;
            counter.className = 'profile-field-help content-word-count';
            body.after(counter);
            body.setAttribute('aria-describedby', [body.getAttribute('aria-describedby'), counter.id].filter(Boolean).join(' '));
        }
        const updatePreview = () => {
            const content = body?.value || '';
            if (counter) {
                const count = (content.match(/\S+/gu) || []).length;
                counter.textContent = `${count} / 300 words${count > 300 ? ' — shorten before submitting' : ''}`;
                counter.classList.toggle('is-over-limit', count > 300);
            }
            if (!livePreview) return;
            livePreview.querySelector('[data-preview-title]').textContent = form.elements.namedItem('title')?.value.trim() || 'Untitled submission';
            livePreview.querySelector('[data-preview-text]').textContent = content || 'Your content will appear here.';
            livePreview.querySelectorAll('[data-preview-field]').forEach((output) => {
                const control = form.elements.namedItem(output.dataset.previewField);
                const value = control?.value.trim() || '';
                output.textContent = value && control.tagName === 'SELECT' ? control.selectedOptions[0].textContent : value;
                output.closest('[data-preview-row]').hidden = !value;
            });
        };
        updatePreview();
        if (livePreview) livePreview.hidden = false;
        form.addEventListener('input', updatePreview);
        form.addEventListener('change', updatePreview);
        window.addEventListener('pageshow', updatePreview);
        form.querySelectorAll('input[type="file"][data-image-preview]').forEach((input) => {
            let preview = document.getElementById(input.dataset.imagePreview);
            const mount = document.getElementById(`${input.dataset.imagePreview}-mount`);
            if (!preview && !mount) return;
            const original = preview?.getAttribute('src');
            let objectUrl = null;
            input.addEventListener('change', () => {
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                const file = input.files?.[0];
                objectUrl = file?.type.startsWith('image/') ? URL.createObjectURL(file) : null;
                if (objectUrl || original) {
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = input.dataset.imagePreview;
                        preview.className = 'article-form-preview';
                        preview.alt = 'Selected image preview';
                        mount.after(preview);
                    }
                    preview.src = objectUrl || original;
                    preview.hidden = false;
                } else {
                    preview?.remove();
                    preview = null;
                }
            });
            window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
        });
        const reset = () => {
            form.removeAttribute('aria-busy');
            form.querySelectorAll('[data-saving-label]').forEach((button) => {
                button.disabled = false;
                button.textContent = button.dataset.savingLabel;
                delete button.dataset.savingLabel;
            });
        };
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.dataset.savingLabel = button.textContent;
                button.textContent = 'Saving…';
                button.disabled = true;
            });
            queueMicrotask(() => { if (event.defaultPrevented) reset(); });
        });
        window.addEventListener('pageshow', reset);
    });
}
