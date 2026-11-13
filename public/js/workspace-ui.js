(() => {
    if (!document.body.classList.contains('workspace-site-body')) return;
    const dialogs = [...document.querySelectorAll('.workspace-dialog')];
    if (!dialogs.length || typeof dialogs[0].showModal !== 'function') return;
    const preferenceKey = 'ssite-workspace-preferences';
    const preferences = { density: 'comfortable', reduceMotion: false };
    try {
        const saved = JSON.parse(localStorage.getItem(preferenceKey) || '{}');
        if (saved?.density === 'compact') preferences.density = 'compact';
        preferences.reduceMotion = saved?.reduceMotion === true;
    } catch (_) { /* Preferences still work for this page when storage is unavailable. */ }
    const applyPreferences = () => {
        document.body.dataset.workspaceDensity = preferences.density;
        document.body.classList.toggle('workspace-reduce-motion', preferences.reduceMotion);
        document.querySelectorAll('[name="workspace-density"]').forEach(input => { input.checked = input.value === preferences.density; });
        document.querySelector('[data-workspace-reduce-motion]').checked = preferences.reduceMotion;
    };
    applyPreferences();
    const jump = document.getElementById('workspace-jump');
    const query = jump.querySelector('[data-workspace-query]');
    const items = [...jump.querySelectorAll('[data-jump-item]')];
    const filter = () => {
        const terms = query.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
        items.forEach(item => { item.hidden = !terms.every(term => item.textContent.toLocaleLowerCase().includes(term)); });
        const count = items.filter(item => !item.hidden).length;
        jump.querySelector('[data-workspace-no-results]').hidden = count !== 0;
        jump.querySelector('[data-workspace-result-count]').textContent = count + (count === 1 ? ' destination' : ' destinations');
    };
    const openDialog = (id) => {
        if (document.querySelector('dialog[open]')) return;
        const dialog = document.getElementById(id);
        if (!dialog) return;
        // Close the account popover before entering a modal.
        document.getElementById('account-menu')?.classList.add('hidden');
        document.getElementById('account-menu-toggle')?.setAttribute('aria-expanded', 'false');
        dialog.showModal();
        document.body.classList.add('workspace-dialog-open');
        if (dialog === jump) { query.value = ''; filter(); query.focus(); }
    };
    document.querySelectorAll('[data-workspace-open]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => openDialog(button.dataset.workspaceOpen));
    });
    dialogs.forEach(dialog => {
        dialog.querySelectorAll('[data-workspace-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('close', () => {
            document.body.classList.remove('workspace-dialog-open');
            if (dialog.id === 'workspace-preferences') document.getElementById('account-menu-toggle')?.focus();
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        });
    });
    query.addEventListener('input', filter);
    jump.addEventListener('keydown', event => {
        const visible = items.filter(item => !item.hidden);
        if (!visible.length) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const index = visible.indexOf(document.activeElement);
            const next = event.key === 'ArrowDown' ? (index + 1) % visible.length : (index <= 0 ? visible.length - 1 : index - 1);
            visible[next].focus();
        }
        if (event.key === 'Enter' && event.target === query) { event.preventDefault(); visible[0].click(); }
    });
    document.addEventListener('keydown', event => {
        const editing = event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable="true"], [role="textbox"]');
        if (event.defaultPrevented || (editing && !jump.open)) return;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && !event.altKey && !event.isComposing) {
            // Do not interrupt another modal or the mobile navigation focus trap.
            if (document.body.classList.contains('workspace-menu-open') || (document.querySelector('dialog[open]') && !jump.open)) return;
            event.preventDefault();
            if (jump.open) jump.close(); else openDialog('workspace-jump');
        }
    });
    const settings = document.getElementById('workspace-preferences');
    settings.addEventListener('change', event => {
        if (event.target.name === 'workspace-density') preferences.density = event.target.value === 'compact' ? 'compact' : 'comfortable';
        if (event.target.matches('[data-workspace-reduce-motion]')) preferences.reduceMotion = event.target.checked;
        applyPreferences();
        let message = 'Preferences saved in this browser.';
        try { localStorage.setItem(preferenceKey, JSON.stringify(preferences)); }
        catch (_) { message = 'Applied for this page. Browser storage is unavailable.'; }
        settings.querySelector('[data-workspace-preference-status]').textContent = message;
    });
})();
