@props(['title' => 'Dashboard', 'description', 'actionUrl' => null, 'actionLabel' => null])
<header class="workspace-dashboard-heading workspace-welcome">
    <div class="workspace-welcome-copy">
        <span class="workspace-eyebrow">SSITE / YOUR WORKSPACE</span>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <div class="workspace-welcome-actions">
            @if ($actionUrl && $actionLabel)
                <a class="dashboard-button" href="{{ $actionUrl }}">{{ $actionLabel }} <x-workspace.icon name="arrow-right" /></a>
            @endif
            <span class="workspace-welcome-member">Welcome back, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}.</span>
        </div>
    </div>
    <div class="workspace-welcome-art" aria-hidden="true">
        <span class="workspace-welcome-orbit"></span>
        <img src="{{ asset('images/Wolf.png') }}" alt="" width="180" height="180">
        <span class="workspace-welcome-stamp">CREATE. CONNECT. INSPIRE.</span>
    </div>
</header>
