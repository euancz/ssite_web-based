@props(['url', 'sponsorName'])

@if ($url)
    @php
        $host = strtolower(rtrim(parse_url($url, PHP_URL_HOST) ?? '', '.'));
        $isFacebook = in_array($host, ['facebook.com', 'fb.com', 'fb.me'], true)
            || str_ends_with($host, '.facebook.com');
    @endphp

    <a href="{{ $url }}" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
        <x-icon :name="$isFacebook ? 'facebook' : 'external-link'" size="md" class="footer-contact-icon" />
        <span>{{ $isFacebook ? $sponsorName.' on Facebook' : 'Visit '.$sponsorName.' online' }}</span>
    </a>
@endif
