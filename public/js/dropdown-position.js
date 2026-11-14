(() => {
    if (!CSS.supports('appearance', 'base-select')) return;

    const isDropdown = (element) => element instanceof HTMLSelectElement
        && !element.multiple && element.size <= 1;

    let anchorSequence = 0;
    const fitBelow = (select) => {
        if (!select.style.getPropertyValue('--dropdown-anchor')) {
            const anchor = `--ssite-dropdown-${++anchorSequence}`;
            select.style.anchorName = anchor;
            select.style.setProperty('--dropdown-anchor', anchor);
        }
        const viewportBottom = window.visualViewport
            ? window.visualViewport.offsetTop + window.visualViewport.height
            : window.innerHeight;
        const available = Math.max(0, viewportBottom - select.getBoundingClientRect().bottom - 18);
        select.style.setProperty('--dropdown-available-height', `${Math.min(288, available)}px`);
    };

    // Delegation also covers feeds replaced after filtering and newly opened dialogs.
    for (const name of ['pointerdown', 'focusin', 'keydown']) {
        document.addEventListener(name, (event) => {
            if (isDropdown(event.target)) fitBelow(event.target);
        }, true);
    }

    let frame = null;
    const updateOpenDropdowns = () => {
        if (frame !== null) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            document.querySelectorAll('select:open').forEach((select) => {
                if (isDropdown(select)) fitBelow(select);
            });
        });
    };

    document.addEventListener('scroll', updateOpenDropdowns, { capture: true, passive: true });
    window.addEventListener('resize', updateOpenDropdowns, { passive: true });
    window.visualViewport?.addEventListener('resize', updateOpenDropdowns, { passive: true });
    window.visualViewport?.addEventListener('scroll', updateOpenDropdowns, { passive: true });
})();
