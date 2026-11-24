@props(['section', 'labelled' => false])
@if (auth()->user()?->isAdviser())
    @php($label = 'Edit '.config('site_sections.'.$section.'.label'))
    <a @class(['section-pencil', 'section-pencil--labelled' => $labelled]) id="edit-{{ $section }}"
       href="{{ route('adviser.site.edit', $section) }}" title="{{ $label }}" aria-label="{{ $label }}">
        <x-icon name="pencil" size="sm" />
        @if ($labelled) {{ $label }} @endif
    </a>
    @if (session('saved_section') === $section)
        <small class="section-saved" role="status">Changes saved</small>
    @endif
@endif
