@props(['id', 'label', 'value' => '', 'placeholder' => '', 'name' => 'q'])

<div class="workspace-list-field workspace-list-search">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="workspace-list-search-input">
        <input id="{{ $id }}" type="search" name="{{ $name }}" maxlength="100" value="{{ $value }}" placeholder="{{ $placeholder }}">
        <button type="submit" aria-label="{{ $label }}">
            <x-icon name="search" size="sm" />
        </button>
    </div>
</div>
