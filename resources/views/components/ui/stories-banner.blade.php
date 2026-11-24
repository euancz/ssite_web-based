@props(['title', 'description', 'eyebrow', 'editSection' => null, 'labelledby' => 'stories-banner-title'])

<section class="stories-banner" aria-labelledby="{{ $labelledby }}">
    <span class="stories-banner-grid stories-banner-grid--top" aria-hidden="true"></span>
    <span class="stories-banner-grid stories-banner-grid--bottom" aria-hidden="true"></span>
    <div class="stories-banner-copy">
        <p class="stories-banner-eyebrow">{{ $eyebrow }}</p>
        <h1 id="{{ $labelledby }}">{{ $title }} @if ($editSection)<x-section-pencil :section="$editSection" />@endif</h1>
        <p class="stories-banner-description">{{ $description }}</p>
    </div>
</section>
