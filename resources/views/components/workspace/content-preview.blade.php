@props(['fields' => []])

{{-- Values are copied with textContent. This preview never renders submitted HTML. --}}
<details class="content-live-preview" data-content-preview hidden>
    <summary>Preview unsaved content</summary>
    <div class="content-live-preview-body">
        <p class="profile-field-help">This preview updates as you type. Changes are saved only when you submit the form.</p>
        <h2 data-preview-title></h2>
        <dl>
            @foreach ($fields as $name => $label)
                <div data-preview-row hidden><dt>{{ $label }}</dt><dd data-preview-field="{{ $name }}"></dd></div>
            @endforeach
        </dl>
        <p class="content-preview-text" data-preview-text></p>
    </div>
</details>
