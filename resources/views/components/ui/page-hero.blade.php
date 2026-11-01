@props([
    'title',
    'description' => null,
    'eyebrow' => null,
    'labelledby' => 'page-hero-title',
    'class' => '',
    'banner' => null,
])

<section {{ $attributes->merge(['class' => trim('page-hero site-hero ' . $class . ($banner ? ' page-hero--banner' : ''))]) }} aria-labelledby="{{ $labelledby }}">
    @if ($banner)
        <img class="page-hero-banner-art" src="{{ asset($banner) }}" alt="" aria-hidden="true">
    @endif
    <div class="page-hero-content{{ $banner ? ' visually-hidden' : '' }}">
        @if ($eyebrow)
            <p class="page-hero-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 id="{{ $labelledby }}">{{ $title }}</h1>
        @if ($description)
            <p class="page-hero-description">{{ $description }}</p>
        @endif
    </div>
    @unless ($banner)
        <div class="page-hero-visual {{ trim((string) $slot) === '' ? 'page-hero-visual-default' : '' }}" aria-hidden="true">
            @if (trim((string) $slot) !== '')
                {{ $slot }}
            @else
                <span class="page-hero-mark">SSITE</span>
                <span class="page-hero-orbit page-hero-orbit-one"></span>
                <span class="page-hero-orbit page-hero-orbit-two"></span>
            @endif
        </div>
    @endunless
</section>
