@props(['title', 'help'])

<div class="article-attachment-dropzone">
    <span class="article-attachment-icon" aria-hidden="true">&#8599;</span>
    <span><strong>{{ $title }}</strong><small>{{ $help }}</small></span>
    {{ $slot }}
</div>
