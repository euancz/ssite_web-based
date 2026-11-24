function initImagePreview(input) {
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
}

export function initContentUploads() {
    document.querySelectorAll('[data-upload-status]').forEach((panel) => {
        const form = panel.closest('form');
        const input = document.getElementById(panel.dataset.uploadInput);
        if (!form || !input || input.form !== form) return;
        if (input.dataset.imagePreview) initImagePreview(input);
        const summary = panel.querySelector('[data-upload-summary]');
        const clear = panel.querySelector('[data-upload-clear]');
        const maxKb = Number(panel.dataset.uploadMaxKb);
        input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), summary.id].filter(Boolean).join(' '));
        const update = () => {
            const file = input.files?.[0];
            const oversized = file && file.size > maxKb * 1024;
            const error = oversized ? `Choose a file no larger than ${maxKb.toLocaleString()} KB.` : '';
            input.setCustomValidity(error);
            summary.classList.toggle('profile-field-error', !!oversized);
            summary.textContent = file
                ? `${file.name} · ${(file.size / 1024).toLocaleString(undefined, { maximumFractionDigits: 1 })} KB${error ? `. ${error}` : ''}`
                : '';
            clear.hidden = !file;
            panel.hidden = !file;
        };
        clear.addEventListener('click', () => {
            input.value = '';
            // Notify the image preview and unsaved-change guard as well.
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        });
        input.addEventListener('change', update);
        form.addEventListener('reset', () => queueMicrotask(update));
        window.addEventListener('pageshow', update);
        update();
    });
}
