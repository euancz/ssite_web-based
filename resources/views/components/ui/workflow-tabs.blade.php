@props(['routeName', 'allowedTabs', 'activeTab', 'pendingCount' => null])

{{-- SECURITY: Render only controller-whitelisted tabs; the server keeps the same role checks and fallback. --}}
@php
    $labels = ['published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'];
    $isAdviser = in_array('pending', $allowedTabs, true);
    $inlineTabs = array_values(array_filter($allowedTabs, fn ($tab) => in_array($tab, $isAdviser ? ['published', 'pending'] : ['published', 'mine', 'archived'], true)));
    $moreTabs = array_values(array_filter($allowedTabs, fn ($tab) => in_array($tab, $isAdviser ? ['rejected', 'archived', 'all'] : [], true)));
    $selectedMoreTab = in_array($activeTab, $moreTabs, true) ? $labels[$activeTab] : null;
@endphp
<nav class="dashboard-tabs" aria-label="Filter posts">
    @foreach ($inlineTabs as $tab)
        <a href="{{ route($routeName, ['tab' => $tab]) }}"
           @class(['dashboard-tab', 'is-active' => $activeTab === $tab])
           @if ($activeTab === $tab) aria-current="page" @endif>
            {{ $labels[$tab] }} @if ($tab === 'pending' && $pendingCount !== null)<span class="dashboard-tab-count">{{ $pendingCount }}</span>@endif
        </a>
    @endforeach
    @if ($moreTabs)
        <details class="dashboard-tabs-more" @if ($selectedMoreTab) open @endif>
            {{-- Keep the current hidden tab visible while exposing the disclosure state to assistive technology. --}}
            <summary class="dashboard-tab {{ $selectedMoreTab ? 'is-active' : '' }}"
                     aria-haspopup="true"
                     aria-expanded="{{ $selectedMoreTab ? 'true' : 'false' }}"
                     aria-controls="{{ str_replace('.', '-', $routeName) }}-more-options">
                {{ $selectedMoreTab ?? 'More' }}
            </summary>
            <div id="{{ str_replace('.', '-', $routeName) }}-more-options" class="dashboard-tabs-menu">
                @foreach ($moreTabs as $tab)
                    <a href="{{ route($routeName, ['tab' => $tab]) }}"
                       @if ($activeTab === $tab) aria-current="page" @endif>{{ $labels[$tab] }}</a>
                @endforeach
            </div>
        </details>
    @endif
</nav>

@once
    @push('scripts')
        <script>
            // Adviser bulk controls appear only after a permitted row is selected.
            document.querySelectorAll('[data-selection-bar]').forEach((bar) => {
                const form = document.getElementById(bar.closest('.dashboard-page')?.querySelector('form[id$="-bulk-archive"]')?.id || '');
                if (!form) return;
                const checkboxes = [...document.querySelectorAll(`input[type="checkbox"][form="${form.id}"]`)];
                const update = () => {
                    const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
                    bar.hidden = selected === 0;
                    const count = bar.querySelector('[data-selection-count]');
                    if (count) count.textContent = `${selected} selected`;
                };
                checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
                update();
            });

            const rowMenus = [...document.querySelectorAll('.article-row-menu')];
            rowMenus.forEach((menu) => {
                const positionFloatingPanel = () => {
                    if (!menu.open || !menu.classList.contains('floating-row-menu')) return;
                    const bounds = menu.querySelector('summary').getBoundingClientRect();
                    const panel = menu.querySelector('.article-row-menu-panel');
                    panel.style.left = `${Math.max(8, Math.min(bounds.right - 150, window.innerWidth - 168))}px`;
                    panel.style.top = `${Math.min(bounds.bottom + 4, window.innerHeight - panel.offsetHeight - 8)}px`;
                };
                menu.addEventListener('toggle', positionFloatingPanel);
                menu.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        menu.open = false;
                        menu.querySelector('summary')?.focus();
                    }
                });
            });
            document.querySelectorAll('.dashboard-tabs-more').forEach((menu) => {
                const summary = menu.querySelector('summary');
                const syncExpanded = () => summary?.setAttribute('aria-expanded', String(menu.open));
                menu.addEventListener('toggle', syncExpanded);
                syncExpanded();
            });
            document.addEventListener('click', (event) => {
                rowMenus.forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; });
                document.querySelectorAll('.dashboard-tabs-more').forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; });
            });
            document.querySelectorAll('.dashboard-tabs-more').forEach((menu) => menu.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') { menu.open = false; menu.querySelector('summary')?.focus(); }
            }));
            window.addEventListener('resize', () => rowMenus.forEach((menu) => menu.dispatchEvent(new Event('toggle'))));
            window.addEventListener('scroll', () => rowMenus.filter((menu) => menu.classList.contains('floating-row-menu')).forEach((menu) => {
                if (menu.open) menu.dispatchEvent(new Event('toggle'));
            }), true);
        </script>
    @endpush
@endonce
