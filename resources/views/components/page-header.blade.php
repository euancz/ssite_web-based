@props(['title', 'subtitle' => null])

<header class="dashboard-page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="dashboard-page-header-actions">{{ $actions }}</div>
    @endif
</header>
