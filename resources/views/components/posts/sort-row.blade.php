{{-- SECURITY: Displayed options are server-whitelisted and tab links omit filter state to reset it. --}}
@props([
    'action', 'routeName', 'tab', 'sort', 'options',
    'filterName' => null, 'filter' => null, 'filterOptions' => [],
    'filterLabel' => 'Filter posts', 'filterDefault' => null,
])

{{-- Keep sort controls compact; tab changes discard sort state and the form omits pagination. --}}
<form class="post-feed-sort" method="GET" action="{{ $action }}" data-auto-submit>
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="post-feed-visually-hidden" for="post-sort-{{ str_replace('.', '-', $routeName) }}">Sort posts</label>
    <select id="post-sort-{{ str_replace('.', '-', $routeName) }}" class="post-feed-filter-control" name="sort" aria-label="Sort posts">
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($filterName && $filterOptions)
        <label class="post-feed-visually-hidden" for="post-filter-{{ str_replace('.', '-', $routeName) }}">{{ $filterLabel }}</label>
        <select id="post-filter-{{ str_replace('.', '-', $routeName) }}" class="post-feed-filter-control" name="{{ $filterName }}" aria-label="{{ $filterLabel }}">
            @foreach ($filterOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $filter === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endif
    @if ($filterName === 'category')<noscript><button type="submit" class="dashboard-button">Apply filters</button></noscript>@endif
</form>

@once
    @push('scripts')
        <script>
            (() => {
                const menuBindings = new WeakSet();
                let activeRequest = null;
                let requestVersion = 0;

                document.addEventListener('change', (event) => {
                    const control = event.target instanceof Element ? event.target : null;
                    const select = control?.closest('select.post-feed-filter-control');
                    if (select?.closest('form.post-feed-sort[data-auto-submit]')) {
                        select.form?.requestSubmit();
                    }
                });

                const initializeResults = (results) => {
                    results.querySelectorAll('[data-post-menu]').forEach((menu) => {
                        if (menuBindings.has(menu)) return;
                        menuBindings.add(menu);
                        const summary = menu.querySelector('summary');
                        const syncExpanded = () => summary?.setAttribute('aria-expanded', String(menu.open));
                        menu.addEventListener('toggle', syncExpanded);
                        menu.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                menu.open = false;
                                summary?.focus({ preventScroll: true });
                            }
                        });
                        syncExpanded();
                    });

                    results.querySelectorAll('[data-selection-bar]').forEach((bar) => {
                        const page = bar.closest('.dashboard-page');
                        const form = page?.querySelector('form[id$="-bulk-archive"]');
                        if (!form) return;
                        const update = () => {
                            const selected = [...results.querySelectorAll(`input[type="checkbox"][form="${form.id}"]`)]
                                .filter((checkbox) => checkbox.checked).length;
                            bar.hidden = selected === 0;
                            const count = bar.querySelector('[data-selection-count]');
                            if (count) count.textContent = `${selected} selected`;
                        };
                        update();
                    });
                };

                const syncToolbar = (currentShell, nextShell) => {
                    const currentTabs = currentShell.querySelector('.dashboard-tabs');
                    const nextTabs = nextShell.querySelector('.dashboard-tabs');
                    if (currentTabs && nextTabs) {
                        currentTabs.querySelectorAll('a').forEach((link) => {
                            const match = [...nextTabs.querySelectorAll('a')].find((nextLink) => nextLink.href === link.href);
                            link.className = match?.className || 'dashboard-tab';
                            if (match?.hasAttribute('aria-current')) link.setAttribute('aria-current', 'page');
                            else link.removeAttribute('aria-current');
                        });
                        const currentDetails = currentTabs.querySelector('.dashboard-tabs-more');
                        const nextDetails = nextTabs.querySelector('.dashboard-tabs-more');
                        if (currentDetails && nextDetails) {
                            currentDetails.open = nextDetails.open;
                            const summary = currentDetails.querySelector('summary');
                            const nextSummary = nextDetails.querySelector('summary');
                            if (summary && nextSummary) {
                                summary.textContent = nextSummary.textContent;
                                summary.className = nextSummary.className;
                                summary.setAttribute('aria-expanded', nextSummary.getAttribute('aria-expanded') || 'false');
                            }
                        }
                    }

                    const currentForm = currentShell.querySelector('.post-feed-sort');
                    const nextForm = nextShell.querySelector('.post-feed-sort');
                    if (currentForm && nextForm) {
                        const activeName = currentForm.contains(document.activeElement)
                            ? document.activeElement.name : null;
                        currentForm.action = nextForm.action;
                        currentForm.innerHTML = nextForm.innerHTML;
                        if (activeName) currentForm.querySelector(`[name="${CSS.escape(activeName)}"]`)?.focus({ preventScroll: true });
                    }
                };

                const loadFeed = async (url, pushHistory = true) => {
                    const currentShell = document.querySelector('.post-feed-shell');
                    const currentResults = currentShell?.querySelector('[data-feed-results]');
                    const error = currentShell?.querySelector('[data-feed-error]');
                    if (!currentShell || !currentResults) return;
                    if (activeRequest) activeRequest.abort();
                    activeRequest = new AbortController();
                    const version = ++requestVersion;
                    currentResults.setAttribute('aria-busy', 'true');
                    error?.setAttribute('hidden', '');
                    const content = currentResults.querySelector('[data-feed-content]');
                    const skeleton = currentResults.querySelector('[data-feed-skeleton]');
                    if (content && skeleton) {
                        content.hidden = true;
                        skeleton.hidden = false;
                    }
                    currentShell.querySelector('[data-feed-status]')?.replaceChildren('Loading posts…');

                    try {
                        const response = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                            credentials: 'same-origin',
                            signal: activeRequest.signal,
                        });
                        if (!response.ok) throw new Error('Feed request failed.');
                        const html = await response.text();
                        if (version !== requestVersion) return;
                        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                        const nextShell = nextDocument.querySelector('.post-feed-shell');
                        const nextResults = nextShell?.querySelector('[data-feed-results]');
                        if (!nextShell || !nextResults) throw new Error('Feed response was incomplete.');

                        syncToolbar(currentShell, nextShell);
                        const updatedResults = nextResults.cloneNode(true);
                        updatedResults.dataset.ajaxUpdated = 'true';
                        updatedResults.setAttribute('aria-busy', 'false');
                        currentResults.replaceWith(updatedResults);
                        initializeResults(updatedResults);
                        currentShell.querySelector('[data-feed-status]')?.replaceChildren('Posts updated.');
                        if (pushHistory) history.pushState({}, '', url);
                    } catch (failure) {
                        if (failure.name !== 'AbortError' && version === requestVersion) {
                            error?.removeAttribute('hidden');
                            const results = document.querySelector('.post-feed-shell [data-feed-results]');
                            const content = results?.querySelector('[data-feed-content]');
                            const skeleton = results?.querySelector('[data-feed-skeleton]');
                            if (content && skeleton) {
                                content.hidden = false;
                                skeleton.hidden = true;
                            }
                            document.querySelector('.post-feed-shell [data-feed-status]')?.replaceChildren('Posts could not be updated.');
                        }
                    } finally {
                        if (version === requestVersion) {
                            document.querySelector('.post-feed-shell [data-feed-results]')?.setAttribute('aria-busy', 'false');
                            activeRequest = null;
                        }
                    }
                };

                const feedUrl = (form) => {
                    const target = new URL(form.action, window.location.href);
                    for (const [key, value] of new FormData(form)) target.searchParams.set(key, value);
                    return target;
                };

                document.addEventListener('submit', (event) => {
                    const form = event.target instanceof Element ? event.target.closest('form.post-feed-sort') : null;
                    if (!form) return;
                    event.preventDefault();
                    loadFeed(feedUrl(form));
                });

                document.addEventListener('click', (event) => {
                    const link = event.target.closest('.post-feed-shell .dashboard-tabs a, .post-feed-shell [data-feed-results] .dashboard-pagination a');
                    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    const target = new URL(link.href, window.location.href);
                    if (target.origin !== window.location.origin) return;
                    event.preventDefault();
                    loadFeed(target);
                });

                document.addEventListener('popstate', () => {
                    if (document.querySelector('.post-feed-shell [data-feed-results]')) loadFeed(window.location.href, false);
                });

                document.addEventListener('click', (event) => {
                    const root = event.target.closest('[data-feed-results][data-ajax-updated="true"]');
                    if (!root) return;
                    const expand = event.target.closest('[data-post-expand]');
                    if (expand) {
                        event.preventDefault();
                        const expanded = expand.getAttribute('aria-expanded') === 'true';
                        const fullText = root.querySelector(`#${CSS.escape(expand.getAttribute('aria-controls'))}`);
                        const excerpt = expand.closest('.post-card-body')?.querySelector('[data-post-excerpt]');
                        if (!fullText || !excerpt) return;
                        fullText.hidden = expanded;
                        excerpt.hidden = !expanded;
                        expand.setAttribute('aria-expanded', String(!expanded));
                        expand.textContent = expanded ? 'See more' : 'See less';
                        return;
                    }

                    const openModal = event.target.closest('[data-modal-open], [data-post-confirm-open]');
                    if (openModal) {
                        const id = openModal.dataset.modalOpen || openModal.dataset.postConfirmOpen;
                        document.getElementById(id)?.showModal();
                        return;
                    }
                    const closeModal = event.target.closest('[data-modal-close], [data-post-confirm-close]');
                    if (closeModal) {
                        const id = closeModal.dataset.modalClose || closeModal.dataset.postConfirmClose;
                        document.getElementById(id)?.close();
                        return;
                    }

                    const share = event.target.closest('[data-post-share]');
                    if (share) {
                        if (navigator.share) {
                            event.preventDefault();
                            navigator.share({ title: share.dataset.shareTitle, url: share.href })
                                .then(() => { const status = share.parentElement?.querySelector('[data-share-status]'); if (status) status.textContent = 'Link shared'; })
                                .catch((failure) => { if (failure.name !== 'AbortError') window.location.assign(share.href); });
                        } else if (navigator.clipboard?.writeText) {
                            event.preventDefault();
                            navigator.clipboard.writeText(share.href)
                                .then(() => { const status = share.parentElement?.querySelector('[data-share-status]'); if (status) status.textContent = 'Link copied'; })
                                .catch(() => window.location.assign(share.href));
                        }
                    }
                });

                document.addEventListener('click', (event) => {
                    document.querySelectorAll('[data-feed-results][data-ajax-updated="true"] [data-post-menu]').forEach((menu) => {
                        if (!menu.contains(event.target)) menu.open = false;
                    });
                });

                document.addEventListener('keydown', (event) => {
                    const menu = event.target.closest('[data-feed-results][data-ajax-updated="true"] [data-post-menu]');
                    if (menu && event.key === 'Escape') {
                        menu.open = false;
                        menu.querySelector('summary')?.focus({ preventScroll: true });
                    }
                });

                document.addEventListener('change', (event) => {
                    const root = event.target.closest('[data-feed-results][data-ajax-updated="true"]');
                    if (!root || !event.target.matches('input[type="checkbox"][form$="-bulk-archive"]')) return;
                    root.querySelectorAll('[data-selection-bar]').forEach((bar) => {
                        const form = bar.closest('.dashboard-page')?.querySelector('form[id$="-bulk-archive"]');
                        if (!form) return;
                        const selected = [...root.querySelectorAll(`input[type="checkbox"][form="${form.id}"]`)].filter((checkbox) => checkbox.checked).length;
                        bar.hidden = selected === 0;
                        const count = bar.querySelector('[data-selection-count]');
                        if (count) count.textContent = `${selected} selected`;
                    });
                });
            })();
        </script>
    @endpush
@endonce
