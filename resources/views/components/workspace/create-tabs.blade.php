@props(['active'])

@php
    $user = auth()->user();
    $modules = collect(config('workspace.modules'))
        ->filter(fn ($module, $key) => $user?->canPostIn($key));
@endphp

@if ($modules->isNotEmpty())
    <nav class="workspace-create-tabs" aria-label="Create content">
        <span class="workspace-create-tabs-label">Create</span>
        <div class="workspace-create-tabs-list" role="list">
            @foreach ($modules as $key => $module)
                <a
                    class="workspace-create-tab {{ $active === $key ? 'is-active' : '' }}"
                    href="{{ route($module['create']) }}"
                    @if ($active === $key) aria-current="page" @endif
                >
                    <x-workspace.icon :name="$module['icon']" />
                    <span>{{ $module['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
