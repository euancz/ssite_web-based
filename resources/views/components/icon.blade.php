@props([
    'name',
    'size' => 'md',
])

@php
    // SECURITY: Names select only this static, allow-listed SVG map; no request or database value becomes markup.
    $icons = [
        'layout-dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect>',
        'file-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
        'archive' => '<rect width="20" height="5" x="2" y="3" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path>',
        'calendar-days' => '<path d="M8 2v3"></path><path d="M16 2v3"></path><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M8 13h.01"></path><path d="M12 13h.01"></path><path d="M16 13h.01"></path><path d="M8 17h.01"></path><path d="M12 17h.01"></path><path d="M16 17h.01"></path>',
        'award' => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle>',
        'chart-column-increasing' => '<path d="M13 17V9"></path><path d="M18 17V5"></path><path d="M3 3v16a2 2 0 0 0 2 2h16"></path><path d="M8 17v-3"></path>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><path d="M16 3.128a4 4 0 0 1 0 7.744"></path><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><circle cx="9" cy="7" r="4"></circle>',
        'circle-check' => '<circle cx="12" cy="12" r="10"></circle><path d="m16 9-5.5 5.5L8 12"></path>',
        'circle-x' => '<circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path>',
        'search' => '<path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle>',
        'bell' => '<path d="M10.268 21a2 2 0 0 0 3.464 0"></path><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"></path>',
        'menu' => '<path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path>',
        'x' => '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"></path>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"></path>',
        'external-link' => '<path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>',
        'plus' => '<path d="M5 12h14"></path><path d="M12 5v14"></path>',
        'minus' => '<path d="M5 12h14"></path>',
        'user-round' => '<circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path>',
        'settings' => '<path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"></path><circle cx="12" cy="12" r="3"></circle>',
        'log-out' => '<path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>',
        'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle>',
        'mail' => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect>',
        'ellipsis' => '<circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>',
        'heart' => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"></path>',
        'trash-2' => '<path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
        'arrow-right' => '<path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path>',
        'arrow-up' => '<path d="m5 12 7-7 7 7"></path><path d="M12 19V5"></path>',
        'info' => '<circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>',
        'circle-alert' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line>',
    ];

    $aliases = [
        'grid' => 'layout-dashboard',
        'file' => 'file-text',
        'calendar' => 'calendar-days',
        'chart' => 'chart-column-increasing',
        'check' => 'circle-check',
        'external' => 'external-link',
        'chevron' => 'chevron-right',
        'trash' => 'trash-2',
    ];
    $iconName = is_string($name) ? ($aliases[$name] ?? $name) : '';
    $iconMarkup = $icons[$iconName] ?? null;
    $sizeTokens = ['sm' => 16, 'md' => 20, 'lg' => 24, 'empty' => 48, 'empty-lg' => 64];
    $sizeToken = is_string($size) && array_key_exists($size, $sizeTokens) ? $size : 'md';

    if ($iconMarkup === null && app()->environment('local')) {
        \Illuminate\Support\Facades\Log::debug('Unknown icon name requested: ' . (is_string($name) ? $name : '[non-string]'));
    }
@endphp

@if ($iconMarkup !== null)
    <svg
        {{ $attributes->class(['ssite-icon', 'ssite-icon--' . $sizeToken]) }}
        width="{{ $sizeTokens[$sizeToken] }}"
        height="{{ $sizeTokens[$sizeToken] }}"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        {!! $iconMarkup !!}
    </svg>
@endif
