(() => {
    const skeleton = document.getElementById('page-loading-skeleton');
    const config = document.getElementById('page-loading-routes');
    if (!skeleton || !config) return;

    const routes = JSON.parse(config.textContent).map(route => ({
        ...route,
        // Escape literal path segments before replacing Laravel route parameters.
        pattern: new RegExp('^' + route.path.split('/').map(segment =>
            /^\{[^}]+\}$/.test(segment) ? '[^/]+' : segment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        ).join('/') + '/?$'),
    }));
    // /articles/create must win over /articles/{article}.
    routes.sort((a, b) => Number(a.path.includes('{')) - Number(b.path.includes('{')));
    const resolve = destination => destination.origin === location.origin
        ? routes.find(route => route.pattern.test(decodeURI(destination.pathname))) : null;

    const block = (type = 'line') => `<span class="skeleton-block loading-${type}"></span>`;
    const repeat = (count, render) => Array.from({ length: count }, render).join('');
    const heading = () => `<div class="loading-heading">${block('title')}${block('subtitle')}</div>`;
    const lines = () => `<div class="loading-lines">${block()}${block()}${block('short')}</div>`;
    const toolbar = () => `<div class="loading-toolbar">${block('control')}${block('control')}${block('control')}</div>`;
    const author = () => `<div class="loading-author">${block('avatar')}${lines()}</div>`;
    const card = () => `<div class="loading-panel">${author()}${block('title')}${lines()}${block('footer')}</div>`;
    const table = () => `<div class="loading-table">${repeat(6, () => `<div class="loading-table-row">${repeat(4, () => block())}</div>`)}</div>`;
    const fields = () => `<div class="loading-fields">${repeat(6, () => `<div class="loading-field">${block('short')}${block('input')}</div>`)}</div>`;
    const layouts = {
        home: () => `${block('hero')}<div class="loading-split">${block('media')}${lines()}</div>${heading()}<div class="loading-grid">${repeat(3, card)}</div>`,
        about: () => `${block('hero')}<div class="loading-split">${block('media')}${lines()}</div>${heading()}<div class="loading-grid">${repeat(3, () => `<div class="loading-panel">${block('portrait')}${lines()}</div>`)}</div>`,
        discover: () => `${block('hero')}${toolbar()}<div class="loading-grid">${repeat(6, () => `<div class="loading-panel">${block('media')}${lines()}</div>`)}</div>`,
        dashboard: () => `${heading()}<div class="loading-grid">${repeat(3, () => `<div class="loading-panel">${block('short')}${block('title')}${block()}</div>`)}</div>${heading()}${table()}`,
        feed: () => `${heading()}${toolbar()}${repeat(3, card)}`,
        detail: () => `${block('control')}<div class="loading-panel">${author()}${heading()}${lines()}${block('media')}${lines()}${lines()}${block('footer')}</div>`,
        form: () => `${heading()}${toolbar()}<div class="loading-panel">${fields()}${block('editor')}${block('control')}</div>`,
        profile: () => `${heading()}<div class="loading-profile"><div class="loading-panel">${block('portrait')}${lines()}${block('control')}</div><div class="loading-panel">${heading()}${fields()}${block('control')}</div></div>`,
        notifications: () => `${heading()}${toolbar()}<div class="loading-panel">${repeat(6, author)}</div>`,
        search: () => `${heading()}${block('input')}${toolbar()}${repeat(4, () => `<div class="loading-panel">${block('title')}${lines()}</div>`)}`,
        table: () => `${heading()}${toolbar()}${table()}`,
    };

    let showTimer;
    const hide = () => {
        clearTimeout(showTimer);
        skeleton.classList.add('hidden');
    };
    const show = route => {
        hide();
        if (!route) return;
        showTimer = setTimeout(() => {
            skeleton.dataset.layout = route.kind;
            skeleton.style.left = '';
            skeleton.style.top = '';
            const workspace = route.workspace && document.querySelector('.workspace-content');
            skeleton.classList.toggle('loading-in-workspace', Boolean(workspace));
            skeleton.classList.toggle('loading-to-workspace', route.workspace && !workspace);
            if (workspace) {
                const bounds = workspace.getBoundingClientRect();
                const headerBottom = document.querySelector('.workspace-topbar')?.getBoundingClientRect().bottom || 0;
                skeleton.style.left = `${Math.max(0, bounds.left)}px`;
                skeleton.style.top = `${Math.max(0, bounds.top, headerBottom)}px`;
            }
            skeleton.querySelector('.page-loading-skeleton-content').innerHTML = layouts[route.kind]();
            skeleton.classList.remove('hidden');
        }, 140);
    };

    document.addEventListener('click', event => {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target instanceof Element && event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
        if (link.closest('.post-feed-shell .dashboard-tabs, .post-feed-shell .dashboard-pagination')) return;
        const destination = new URL(link.href, location.href);
        if (destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
        // Let unsaved-change guards and AJAX handlers finish cancelling navigation.
        queueMicrotask(() => {
            if (!event.defaultPrevented && destination.href !== location.href) show(resolve(destination));
        });
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.matches('.post-feed-sort')) return;
        const submitter = event.submitter;
        const target = submitter?.getAttribute('formtarget') ?? form.target;
        const method = (submitter?.getAttribute('formmethod') ?? form.method).toLowerCase();
        if ((target && target !== '_self') || method === 'dialog') return;
        const destination = new URL(submitter?.getAttribute('formaction') ?? (form.action || location.href), location.href);
        queueMicrotask(() => {
            if (event.defaultPrevented || destination.origin !== location.origin) return;
            // Mutations can redirect or return validation errors. Keep their current
            // page shape rather than guessing a destination from an action URL.
            show(resolve(method === 'get' ? destination : new URL(location.href)));
        });
    });
    window.addEventListener('pageshow', hide);
    window.addEventListener('pagehide', hide);
    window.addEventListener('beforeunload', event => {
        queueMicrotask(() => {
            if (event.defaultPrevented) hide();
        });
    });
})();
