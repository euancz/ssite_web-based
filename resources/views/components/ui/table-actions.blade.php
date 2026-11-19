@props(['id', 'label'])

<button type="button" class="table-actions-trigger" popovertarget="{{ $id }}" aria-label="{{ $label }}">
    <x-icon name="ellipsis" />
</button>
<div id="{{ $id }}" class="table-actions-panel" popover>
    {{ $slot }}
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/table-actions.css') }}?v={{ filemtime(public_path('css/table-actions.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/table-actions.js') }}?v={{ filemtime(public_path('js/table-actions.js')) }}" defer></script>
    @endpush
@endonce
