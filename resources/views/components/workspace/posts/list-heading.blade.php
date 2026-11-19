@props(['context', 'title', 'description' => null])

<header class="post-feed-heading">
    <div>
        <span class="workspace-library-context">{{ $context }}</span>
        <h1>{{ $title }}</h1>
        @if ($description)<p>{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</header>
