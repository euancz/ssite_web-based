export function initContentUploads(form) {
    form.querySelectorAll('[data-upload-status]').forEach((panel) => {
        const input = document.getElementById(panel.dataset.uploadInput);
        if (!input || input.form !== form) return;
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
