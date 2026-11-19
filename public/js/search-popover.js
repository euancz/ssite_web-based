(() => {
    const openLists = new Map();
    const viewport = window.visualViewport;

    const place = (list, field) => {
        const bounds = field.getBoundingClientRect();
        const top = viewport?.offsetTop ?? 0;
        const bottom = top + (viewport?.height ?? window.innerHeight);
        const gap = 8;
        const below = bottom - bounds.bottom - gap * 2;
        const above = bounds.top - top - gap * 2;
        const opensUp = below < 180 && above > below;
        const height = Math.max(0, Math.min(520, (bottom - top) * .65, opensUp ? above : below));
        Object.assign(list.style, {
            left: `${bounds.left}px`,
            width: `${bounds.width}px`,
            maxHeight: `${height}px`,
            top: opensUp ? 'auto' : `${bounds.bottom + gap}px`,
            bottom: opensUp ? `${window.innerHeight - bounds.top + gap}px` : 'auto',
        });
    };

    let frame;
    const reposition = () => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            openLists.forEach((field, list) => {
                if (field.isConnected && list.isConnected) place(list, field);
                else openLists.delete(list);
            });
        });
    };
    document.addEventListener('scroll', reposition, { capture: true, passive: true });
    window.addEventListener('resize', reposition, { passive: true });
    viewport?.addEventListener('resize', reposition, { passive: true });
    viewport?.addEventListener('scroll', reposition, { passive: true });

    window.searchPopover = {
        show(list, field) {
            openLists.set(list, field);
            place(list, field);
            list.classList.remove('hidden');
        },
        hide(list) {
            openLists.delete(list);
            list.classList.add('hidden');
        },
        reveal(list, option) {
            const item = option.getBoundingClientRect();
            const menu = list.getBoundingClientRect();
            if (item.top < menu.top) list.scrollTop += item.top - menu.top;
            else if (item.bottom > menu.bottom) list.scrollTop += item.bottom - menu.bottom;
        },
    };
})();
