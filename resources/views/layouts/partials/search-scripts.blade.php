{{-- Search suggestions render text safely and use the throttled endpoint. --}}
    {{-- SECURITY: Suggestions use plain-text DOM nodes, short queries are rejected, and the endpoint is throttled. --}}
    <script>
        (() => {
            const inputs = [...document.querySelectorAll('.search-form [role="combobox"]')];
            const suggestionsUrl = @json(route('search.suggest'));
            const resultsUrl = @json(route('search.index'));
            const typeLabels = { articles: 'Articles', activities: 'Activities', achievements: 'Achievements', documents: 'Documents', liquidation: 'Liquidation', officers: 'Officers' };
            const typeIcons = { articles: '▤', activities: '◷', achievements: '★', documents: '▧', liquidation: '◫', officers: '♙' };
            let timer = null;
            let request = null;
            let sequence = 0;
            let activeIndex = -1;

            const close = (shell) => {
                const input = shell.querySelector('[role="combobox"]');
                const list = shell.querySelector('[role="listbox"]');
                window.clearTimeout(timer);
                sequence++;
                list.classList.add('hidden');
                list.replaceChildren();
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
                activeIndex = -1;
                if (request) request.abort();
            };

            const appendHighlighted = (node, value, words) => {
                const terms = [...new Set(words.filter(Boolean))].sort((a, b) => b.length - a.length);
                if (!terms.length) { node.textContent = value; return; }
                const pattern = new RegExp(`(${terms.map((term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`, 'giu');
                let offset = 0;
                for (const match of value.matchAll(pattern)) {
                    const position = match.index;
                    if (position > offset) node.append(document.createTextNode(value.slice(offset, position)));
                    const mark = document.createElement('mark');
                    mark.textContent = match[0];
                    node.append(mark);
                    offset = position + match[0].length;
                }
                node.append(document.createTextNode(value.slice(offset)));
            };

            const optionLink = (item, words) => {
                const link = document.createElement('a');
                link.className = 'search-suggestion-option';
                link.href = item.url;
                link.setAttribute('role', 'option');
                link.id = `search-option-${sequence}-${Math.random().toString(36).slice(2)}`;
                link.setAttribute('aria-selected', 'false');
                const copy = document.createElement('span');
                copy.className = 'search-suggestion-copy';
                const title = document.createElement('strong');
                appendHighlighted(title, item.title, words);
                const excerpt = document.createElement('span');
                excerpt.className = 'search-suggestion-excerpt';
                appendHighlighted(excerpt, item.excerpt || item.meta || '', words);
                copy.append(title, excerpt);
                link.append(copy);
                if (item.meta) {
                    const meta = document.createElement('small');
                    meta.className = 'search-suggestion-meta';
                    appendHighlighted(meta, item.meta, words);
                    link.append(meta);
                }
                return link;
            };

            const showResults = (shell, list, items, query) => {
                list.replaceChildren();
                const words = query.trim().split(/\s+/).slice(0, 5);
                const groups = new Map();
                items.forEach((item) => {
                    if (!groups.has(item.type)) groups.set(item.type, []);
                    groups.get(item.type).push(item);
                });
                groups.forEach((entries, type) => {
                    const heading = document.createElement('div');
                    heading.className = 'search-suggestion-group';
                    const icon = document.createElement('span');
                    icon.className = 'search-suggestion-icon';
                    icon.setAttribute('aria-hidden', 'true');
                    icon.textContent = typeIcons[type] || '•';
                    const label = document.createElement('span');
                    label.textContent = typeLabels[type] || 'Results';
                    heading.append(icon, label);
                    list.append(heading);
                    entries.slice(0, 3).forEach((item) => list.append(optionLink(item, words)));
                });

                if (!items.length) {
                    const empty = document.createElement('p');
                    empty.className = 'search-suggestion-message';
                    empty.textContent = `No suggestions for “${query}”.`;
                    list.append(empty);
                }
                const all = document.createElement('a');
                all.className = 'search-suggestion-all';
                all.href = `${resultsUrl}?${new URLSearchParams({ q: query })}`;
                all.setAttribute('role', 'option');
                all.id = `search-option-${sequence}-all`;
                all.setAttribute('aria-selected', 'false');
                all.textContent = `See all results for “${query}”`;
                list.append(all);
                list.classList.remove('hidden');
                shell.querySelector('[role="combobox"]').setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            };

            const fetchSuggestions = (input) => {
                const shell = input.closest('.search-shell');
                const list = shell.querySelector('[role="listbox"]');
                const query = input.value.trim().replace(/\s+/g, ' ');
                window.clearTimeout(timer);
                sequence++;
                const currentSequence = sequence;
                if (request) request.abort();
                if (query.length < 2) { close(shell); return; }

                list.replaceChildren();
                const loading = document.createElement('div');
                loading.className = 'search-suggestion-loading';
                loading.setAttribute('role', 'status');
                const loadingLabel = document.createElement('span');
                loadingLabel.className = 'visually-hidden';
                loadingLabel.textContent = 'Searching';
                loading.append(loadingLabel);
                for (let row = 0; row < 3; row++) {
                    const skeletonRow = document.createElement('span');
                    skeletonRow.className = 'skeleton-block search-suggestion-skeleton-row';
                    skeletonRow.setAttribute('aria-hidden', 'true');
                    loading.append(skeletonRow);
                }
                list.append(loading);
                list.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                timer = window.setTimeout(async () => {
                    request = new AbortController();
                    try {
                        const response = await fetch(`${suggestionsUrl}?${new URLSearchParams({ q: query })}`, {
                            headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal,
                        });
                        if (!response.ok) {
                            if (currentSequence === sequence) close(shell);
                            return;
                        }
                        const payload = await response.json();
                        if (currentSequence !== sequence || input.value.trim().replace(/\s+/g, ' ') !== query) return;
                        showResults(shell, list, payload.results || [], query);
                    } catch (_) {
                        // SECURITY: Network and session failures leave the form usable and expose no response data.
                        if (currentSequence === sequence) close(shell);
                    }
                }, 250);
            };

            inputs.forEach((input) => {
                input.addEventListener('input', () => fetchSuggestions(input));
                input.addEventListener('keydown', (event) => {
                    const shell = input.closest('.search-shell');
                    const list = shell.querySelector('[role="listbox"]');
                    const options = [...list.querySelectorAll('[role="option"]')];
                    if (event.key === 'Escape') { close(shell); return; }
                    if (event.key === 'ArrowDown' && !list.classList.contains('hidden')) {
                        event.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, options.length - 1);
                    } else if (event.key === 'ArrowUp' && !list.classList.contains('hidden')) {
                        event.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                    } else if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                        event.preventDefault();
                        window.location.assign(options[activeIndex].href);
                    } else return;

                    options.forEach((option, index) => option.setAttribute('aria-selected', String(index === activeIndex)));
                    const active = options[activeIndex];
                    if (active) {
                        input.setAttribute('aria-activedescendant', active.id);
                        active.scrollIntoView({ block: 'nearest' });
                    } else input.removeAttribute('aria-activedescendant');
                });
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.search-shell').forEach((shell) => {
                    if (!shell.contains(event.target)) close(shell);
                });
            });
            window.addEventListener('popstate', () => document.querySelectorAll('.search-shell').forEach(close));
            window.addEventListener('pagehide', () => { if (request) request.abort(); });
        })();
    </script>
