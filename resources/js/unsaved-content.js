// A navigation warning only: content is never copied to browser storage.
export function protectUnsavedContent(form) {
    const snapshot = () => JSON.stringify(Array.from(form.elements)
        .filter((control) => control.matches('input, select, textarea')
            && !control.matches(':disabled')
            && !['hidden', 'submit', 'button', 'reset'].includes(control.type))
        .map((control) => {
            if (control.type === 'file') {
                return Array.from(control.files, (file) => [file.name, file.size, file.lastModified]);
            }
            if (['checkbox', 'radio'].includes(control.type)) return [control.value, control.checked];
            if (control.multiple) return Array.from(control.selectedOptions, (option) => option.value);
            return control.value;
        }));

    const baseline = snapshot();
    const hasValidationErrors = !!document.querySelector('[data-form-errors]');
    const status = document.createElement('p');
    status.className = 'profile-field-help';
    status.dataset.unsavedStatus = '';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    form.prepend(status);
    let guarded = false;
    let submitting = false;
    const warn = (event) => {
        event.preventDefault();
        event.returnValue = '';
    };
    const update = () => {
        const dirty = hasValidationErrors || snapshot() !== baseline;
        const shouldGuard = dirty && !submitting;
        if (shouldGuard !== guarded) {
            window[shouldGuard ? 'addEventListener' : 'removeEventListener']('beforeunload', warn);
            guarded = shouldGuard;
        }
        const message = dirty ? 'You have unsaved changes.' : '';
        if (status.textContent !== message) status.textContent = message;
        status.hidden = !dirty;
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('reset', () => queueMicrotask(update));
    form.addEventListener('submit', (event) => {
        submitting = !event.defaultPrevented;
        update();
        // Another listener may cancel submission after this listener runs.
        queueMicrotask(() => {
            if (event.defaultPrevented) {
                submitting = false;
                update();
            }
        });
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        update();
    });
    update();
}
