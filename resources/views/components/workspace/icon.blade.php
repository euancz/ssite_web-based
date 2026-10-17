@props(['name'])

<svg class="workspace-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('grid') <rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/> @break
        @case('file') <path d="M7 3.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5a1.5 1.5 0 0 1 1-1.5Z"/><path d="M14 4v4h4M9 13h6M9 16.5h6"/> @break
        @case('archive') <path d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('calendar') <rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7.5 3v4M16.5 3v4M3.5 9.5h17M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01"/> @break
        @case('award') <circle cx="12" cy="8.5" r="5.5"/><path d="m8.5 13-1 8 4.5-2.5 4.5 2.5-1-8"/> @break
        @case('chart') <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/> @break
        @case('users') <path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 4.3a3.5 3.5 0 0 1 0 6.8M17 14.5h.5a4 4 0 0 1 4 4V20"/> @break
        @case('check') <path d="M20 7 10 17l-5-5"/><circle cx="12" cy="12" r="9"/> @break
        @case('external') <path d="M14 4h6v6M20 4l-9 9"/><path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('search') <circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/> @break
        @case('bell') <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('chevron') <path d="m9 18 6-6-6-6"/> @break
    @endswitch
</svg>
