@props([
    'name',
    'size' => 'md',
])

@php
    // SECURITY: Names select only this static, allow-listed SVG map; no request or database value becomes markup.
    $icons = [
        'table' => '<rect x="3" y="3" width="18" height="18" rx="2" /><path d="M3 9h18M3 15h18M9 3v18M15 3v18" />',
        'clear-format' => '<path d="M4 4h14M11 4l-4 16M3 20h9m4-7 6 6m0-6-6 6" />',
        'redo' => '<path d="M21 10H10a7 7 0 0 0 0 14m11-14-5-5m5 5-5 5" transform="translate(0 -2)" />',
        'undo' => '<path d="M3 10h11a7 7 0 0 1 0 14M3 10l5-5M3 10l5 5" transform="translate(0 -2)" />',
        'code-block' => '<rect x="2" y="3" width="20" height="18" rx="2" /><path d="m8 8-4 4 4 4m8-8 4 4-4 4m-3-9-2 10" />',
        'indent' => '<path d="M10 5h11M10 12h11M10 19h11M2 8l4 4-4 4" />',
        'outdent' => '<path d="M10 5h11M10 12h11M10 19h11M6 8l-4 4 4 4" />',
        'strikethrough' => '<path d="M16 5c-2-2-9-2-9 2 0 2 2 3 5 3M8 19c3 2 9 1 9-3 0-2-2-3-4-3M3 12h18" />',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z" />',
        'compass' => '<circle cx="12" cy="12" r="10" /><path d="m16 8-2 6-6 2 2-6z" />',
        'receipt' => '<path d="M4 3l2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1v18l-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1z" /><path d="M8 8h8M8 12h8M8 16h5" />',
        'folder' => '<path d="M3 7V5a2 2 0 0 1 2-2h5l2 4h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
        'bold' => '<path d="M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8" />',
        'italic' => '<line x1="19" x2="10" y1="4" y2="4" /><line x1="14" x2="5" y1="20" y2="20" /><line x1="15" x2="9" y1="4" y2="20" />',
        'heading-2' => '<path d="M4 12h8" /><path d="M4 18V6" /><path d="M12 18V6" /><path d="M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1" />',
        'quote' => '<path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z" /><path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z" />',
        'code' => '<path d="m16 18 6-6-6-6" /><path d="m8 6-6 6 6 6" />',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" /><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />',
        'list' => '<path d="M3 5h.01" /><path d="M3 12h.01" /><path d="M3 19h.01" /><path d="M8 5h13" /><path d="M8 12h13" /><path d="M8 19h13" />',
        'list-ordered' => '<path d="M11 5h10" /><path d="M11 12h10" /><path d="M11 19h10" /><path d="M4 4h1v5" /><path d="M4 9h2" /><path d="M6.5 20H3.4c0-1 2.6-1.925 2.6-3.5a1.5 1.5 0 0 0-2.6-1.02" />',
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
        'facebook' => '<path fill="currentColor" stroke="none" d="M14 22v-9h3l.5-4H14V7c0-1.16.32-2 2-2h2V1.4A26 26 0 0 0 15 1c-3 0-5 1.82-5 5v3H7v4h3v9z" />',
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
