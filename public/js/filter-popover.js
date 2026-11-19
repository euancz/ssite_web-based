(() => {
    const initialized = new WeakSet();
    const panels = () => document.querySelectorAll('[data-filter-popover]');
    const initialize = () => panels().forEach((panel) => {
        if (initialized.has(panel)) return;
        initialized.add(panel);
        const summary = panel.querySelector('summary');
        const fields = [...panel.querySelectorAll('select')];
        let saved = fields.map((field) => field.value);
        const cancel = (focus = false) => {
            fields.forEach((field, index) => { field.value = saved[index]; });
            panel.open = false;
            if (focus) summary.focus({ preventScroll: true });
        };
        panel.cancelFilters = cancel;
        const position = () => {
            const popup = panel.querySelector('.site-secondary-filters');
            const trigger = summary.getBoundingClientRect();
            const viewport = window.visualViewport;
            const viewportLeft = viewport?.offsetLeft || 0;
            const viewportWidth = viewport?.width || document.documentElement.clientWidth;
            popup.style.top = '100%';
            popup.style.bottom = 'auto';
            popup.style.left = '0px';
            popup.style.right = 'auto';
            popup.style.maxWidth = `${Math.max(0, viewportWidth - 24)}px`;
            const bounds = popup.getBoundingClientRect();
            const desiredLeft = trigger.right - bounds.width;
            const left = Math.max(viewportLeft + 12, Math.min(desiredLeft, viewportLeft + viewportWidth - 12 - bounds.width));
            // Use the measured zero position, including borders and ancestor offsets.
            popup.style.left = `${left - bounds.left}px`;
        };
        panel.addEventListener('toggle', () => {
            summary.setAttribute('aria-expanded', String(panel.open));
            if (panel.open) {
                panels().forEach((other) => { if (other !== panel && other.open) other.cancelFilters?.(); });
                position();
            }
        });
        summary.setAttribute('aria-expanded', String(panel.open));
        // Selections are staged until Apply, including controls with inline auto-submit.
        panel.addEventListener('change', (event) => {
            if (!event.target.matches('select')) return;
            event.stopImmediatePropagation();
            if (event.target.name === 'type') {
                const category = panel.querySelector('[name="category"]');
                if (category) category.value = '';
            }
        }, true);
        panel.querySelector('[data-filter-cancel]').addEventListener('click', () => cancel(true));
        summary.addEventListener('click', (event) => {
            if (panel.open) { event.preventDefault(); cancel(); }
        });
        panel.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            event.preventDefault();
            event.stopPropagation();
            cancel(true);
        });
        panel.commitFilters = () => {
            saved = fields.map((field) => field.value);
            panel.open = false;
        };
        panel.positionFilters = position;
    });
    document.addEventListener('click', (event) => {
        panels().forEach((panel) => { if (panel.open && !panel.contains(event.target)) panel.cancelFilters?.(); });
    });
    document.addEventListener('submit', (event) => {
        event.target.querySelectorAll('[data-filter-popover]').forEach((panel) => panel.commitFilters?.());
    }, true);
    let frame = null;
    const updatePositions = () => {
        if (frame !== null) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            panels().forEach((panel) => { if (panel.open) panel.positionFilters?.(); });
        });
    };
    window.addEventListener('resize', updatePositions, { passive: true });
    window.visualViewport?.addEventListener('resize', updatePositions, { passive: true });
    window.visualViewport?.addEventListener('scroll', updatePositions, { passive: true });
    // Keep pending selections and the open card when the page moves.
    document.addEventListener('scroll', (event) => {
        if ([...panels()].some((panel) => panel.open && panel.querySelector('.site-secondary-filters').contains(event.target))) return;
        updatePositions();
    }, { capture: true, passive: true });
    initialize();
    new MutationObserver(initialize).observe(document.querySelector('main') || document.body, { childList: true, subtree: true });
})();
