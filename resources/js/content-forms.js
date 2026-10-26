// Progressive enhancement: every form remains usable with ordinary HTML submission.
export function initContentForms() {
    document.querySelector('[data-form-errors]')?.focus();
    document.querySelectorAll('[data-content-form]').forEach((form) => {
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
        form.addEventListener('submit', () => {
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.dataset.savingLabel = button.textContent;
                button.textContent = 'Saving…';
                button.disabled = true;
            });
        });
        window.addEventListener('pageshow', reset);
    });
}
