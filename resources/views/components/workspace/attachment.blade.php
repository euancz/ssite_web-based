@props(['title', 'help', 'helpId' => null])

<div class="article-attachment-dropzone">
    <span class="article-attachment-icon" aria-hidden="true">&#8599;</span>
    <span>
        <strong>{{ $title }}</strong>
        <small @if ($helpId) id="{{ $helpId }}" @endif>{{ $help }}</small>
    </span>
    {{ $slot }}
</div>
