(() => {
    const root = document.querySelector('body.main-site-body');
    if (!root) return;

    const fields = '.search-form input[name="q"], .main-search-field input[name="q"], .documents-search input[name="q"], .transparency-search input[name="q"]';
    const listings = '.discover-filters, .documents-filters, .transparency-filters, form.post-feed-sort';
    const initializedInputs = new WeakSet();
    const initializedForms = new WeakSet();

    const submit = (form) => {
        if (form?.checkValidity()) form.requestSubmit();
    };
    const clearKeyword = (input) => {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
        // The header owns global suggestions; page filters submit only to their own route.
        if (!input.closest('.search-form')) {
            if (input.required) {
                // The all-content results form requires a keyword. Return to its empty state.
                window.location.assign(input.form.action);
            } else submit(input.form);
        }
    };

    const initializeInput = (input) => {
        if (initializedInputs.has(input)) return;
        initializedInputs.add(input);
        const field = input.parentElement;
        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'search-clear-button';
        clear.dataset.searchClear = '';
        clear.setAttribute('aria-label', 'Clear search');
        const mark = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        mark.classList.add('search-clear-icon');
        mark.setAttribute('viewBox', '0 0 24 24');
        mark.setAttribute('aria-hidden', 'true');
        mark.setAttribute('focusable', 'false');
        mark.setAttribute('fill', 'none');
        mark.setAttribute('stroke', 'currentColor');
        mark.setAttribute('stroke-width', '1.8');
        mark.setAttribute('stroke-linecap', 'round');
        const cross = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        cross.setAttribute('d', 'm6 6 12 12M6 18 18 6');
        mark.append(cross);
        clear.append(mark);
        // Put clear immediately after the field in keyboard order, ahead of Search.
        input.after(clear);
        const sync = () => {
            clear.hidden = input.value.length === 0;
            field.classList.toggle('has-search-value', !clear.hidden);
        };
        input.addEventListener('input', sync);
        input.form?.addEventListener('reset', () => requestAnimationFrame(sync));
        clear.addEventListener('click', () => clearKeyword(input));
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || event.isComposing) return;
            // An open header suggestion menu gets the first Escape; the next clears text.
            if (input.getAttribute('aria-expanded') === 'true') return;
            if (!input.value) return;
            event.preventDefault();
            event.stopPropagation();
            clearKeyword(input);
        }, true);
        sync();
    };

    const initializeFilters = (form) => {
        // Feed updates replace form children while retaining the form element itself.
        if (form.querySelector('[data-active-filters]')) return;
        if (!initializedForms.has(form)) {
            initializedForms.add(form);
            form.setAttribute('role', 'search');
            if (!form.hasAttribute('aria-label')) form.setAttribute('aria-label', 'Filter this page');
        }
        const active = [];
        const keyword = form.querySelector('input[name="q"]');
        if (keyword?.value.trim()) active.push({ control: keyword, label: 'Search', value: keyword.value.trim(), reset: '' });
        form.querySelectorAll('select[name]').forEach((control) => {
            const reset = control.dataset.filterDefault ?? control.options[0]?.value ?? '';
            if (control.value === reset || control.disabled) return;
            const labelCopy = control.labels?.[0]?.cloneNode(true);
            labelCopy?.querySelectorAll('select, input, svg').forEach((node) => node.remove());
            const label = labelCopy?.textContent.trim()
                || control.getAttribute('aria-label') || control.name.replaceAll('_', ' ');
            active.push({ control, label, value: control.selectedOptions[0]?.textContent.trim() || control.value, reset });
        });
        if (!active.length) return;

        const summary = document.createElement('div');
        summary.className = 'site-filter-summary';
        summary.dataset.activeFilters = '';
        summary.setAttribute('role', 'group');
        summary.setAttribute('aria-label', 'Active filters');
        const label = document.createElement('span');
        label.className = 'site-filter-summary-label';
        label.textContent = 'Filtered by';
        summary.append(label);
        for (const filter of active) {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'site-filter-chip';
            chip.setAttribute('aria-label', `Remove ${filter.label}: ${filter.value}`);
            const text = document.createElement('span');
            text.textContent = `${filter.label}: ${filter.value}`;
            const close = document.createElement('span');
            close.setAttribute('aria-hidden', 'true');
            close.textContent = '\u00d7';
            chip.append(text, close);
            chip.addEventListener('click', () => {
                filter.control.value = filter.reset;
                filter.control.dispatchEvent(new Event('input', { bubbles: true }));
                filter.control.focus();
                submit(form);
            });
            summary.append(chip);
        }
        const reset = form.querySelector(':scope > .site-filter-reset');
        if (reset) {
            reset.setAttribute('aria-label', 'Clear all filters');
            summary.append(reset);
        }
        form.append(summary);
    };

    const initialize = () => {
        root.querySelectorAll(fields).forEach(initializeInput);
        root.querySelectorAll(listings).forEach(initializeFilters);
    };
    initialize();
    // Only schedule for replaced or inserted elements, not loading/status text changes.
    let pending = false;
    new MutationObserver((mutations) => {
        if (pending || !mutations.some((mutation) => [...mutation.addedNodes].some((node) => node.nodeType === Node.ELEMENT_NODE))) return;
        pending = true;
        queueMicrotask(() => { pending = false; initialize(); });
    }).observe(root.querySelector('main') || root, { childList: true, subtree: true });
})();