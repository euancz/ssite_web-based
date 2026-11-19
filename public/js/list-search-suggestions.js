(() => {
    const closeSuggestions = new WeakMap();
    let nextId = 0;

    const initialize = (input) => {
        if (!(input instanceof HTMLInputElement) || !input.matches('input[type="search"][name]:not([role="combobox"])')) return;
        const form = input.form;
        if (!form || form.method !== 'get') return;

        const field = input.parentElement;
        const list = document.createElement('div');
        list.id = `list-search-suggestions-${++nextId}`;
        list.className = 'search-suggestions hidden';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', 'Search suggestions');
        field.classList.add('list-search-with-suggestions');
        field.append(list);
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-controls', list.id);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('autocomplete', 'off');

        let timer;
        let request;
        let active = -1;
        let values = [];

        const close = () => {
            window.clearTimeout(timer);
            request?.abort();
            window.searchPopover.hide(list);
            list.replaceChildren();
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            values = [];
            active = -1;
        };
        closeSuggestions.set(input, close);

        const choose = (index) => {
            const value = values[index];
            if (value !== null) {
                input.value = [...value].slice(0, input.maxLength > 0 ? input.maxLength : 100).join('');
            }
            close();
            form.requestSubmit();
        };

        const show = (term, matches = []) => {
            values = [...matches, null];
            active = -1;
            input.removeAttribute('aria-activedescendant');
            list.replaceChildren();
            values.forEach((value, index) => {
                const option = document.createElement('div');
                option.id = `${list.id}-${index}`;
                option.className = value === null ? 'search-suggestion-all' : 'search-suggestion-option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = value ?? `Search for “${term}”`;
                option.addEventListener('mousedown', (event) => event.preventDefault());
                option.addEventListener('click', () => choose(index));
                list.append(option);
            });
            window.searchPopover.show(list, input);
            input.setAttribute('aria-expanded', 'true');
        };

        const suggest = () => {
            close();
            const term = input.value.trim();
            if (!term) return;
            show(term);
            if ([...term].length < 2) return;

            timer = window.setTimeout(async () => {
                const target = new URL(form.action);
                if (target.origin !== window.location.origin) return;
                for (const [name, value] of new FormData(form)) {
                    if (name !== '_token' && name !== 'page') target.searchParams.set(name, value);
                }
                target.searchParams.delete('page');
                target.searchParams.set(input.name, term);
                target.searchParams.set('_suggest', '1');
                const controller = new AbortController();
                request = controller;
                try {
                    const response = await fetch(target, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });
                    if (!response.ok) return;
                    const { results } = await response.json();
                    if (controller.signal.aborted || !input.isConnected || input.value.trim() !== term || !Array.isArray(results)) return;
                    // Keep the active option stable while the request completes.
                    if (active < 0) show(term, results);
                } catch {
                    // The form still submits normally if suggestions are unavailable.
                }
            }, 250);
        };

        input.addEventListener('input', (event) => {
            if (document.activeElement === input && !event.isComposing) suggest();
        });
        input.addEventListener('compositionstart', close);
        input.addEventListener('compositionend', suggest);
        input.addEventListener('focus', suggest);
        input.addEventListener('blur', close);
        input.addEventListener('keydown', (event) => {
            if (event.isComposing || list.classList.contains('hidden')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close();
            } else if (event.key === 'Enter' && active >= 0) {
                event.preventDefault();
                choose(active);
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                active = event.key === 'ArrowDown' ? (active + 1) % values.length
                    : (active <= 0 ? values.length : active) - 1;
                [...list.children].forEach((option, index) => option.setAttribute('aria-selected', String(index === active)));
                const option = list.children[active];
                input.setAttribute('aria-activedescendant', option.id);
                window.searchPopover.reveal(list, option);
            }
        });
        suggest();
    };

    const closeInForm = (event) => {
        event.target.querySelectorAll('input[role="combobox"]').forEach((input) => closeSuggestions.get(input)?.());
    };
    document.addEventListener('focusin', (event) => initialize(event.target));
    document.addEventListener('submit', closeInForm);
    document.addEventListener('reset', closeInForm);
    initialize(document.activeElement);
})();
