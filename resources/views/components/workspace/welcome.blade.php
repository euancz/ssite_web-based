@props(['title' => 'Dashboard', 'description', 'actionUrl' => null, 'actionLabel' => null])
<header class="workspace-dashboard-heading workspace-welcome">
    <div class="workspace-welcome-copy">
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </div>
    @if ($actionUrl && $actionLabel)
        <div class="workspace-welcome-actions">
            <a class="dashboard-button" href="{{ $actionUrl }}">{{ $actionLabel }} <x-workspace.icon name="arrow-right" /></a>
        </div>
    @endif
</header>
