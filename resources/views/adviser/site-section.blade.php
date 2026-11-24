@extends('layouts.app')
@inject('siteContent', 'App\Support\SiteContent')
@section('title', 'Edit '.$definition['label'])

@section('content')
<section class="section-editor">
    <header class="section-editor-heading">
        <a class="section-editor-back" href="{{ route($definition['route']) }}#edit-{{ $section }}">&#8592; Back to page</a>
        <h1>{{ $definition['label'] }}</h1>
        <p>Save your changes to publish them on the website.</p>
    </header>
    <form method="POST" enctype="multipart/form-data" action="{{ route('adviser.site.update', $section) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision" value="{{ old('revision', $revision) }}">
        @if ($errors->any())
            <div role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
                @error('revision')
                    <a href="{{ route('adviser.site.edit', $section) }}">Reload current section</a>
                @enderror
            </div>
        @endif
        @foreach ($definition['fields'] as $field => $default)
            <div class="section-editor-field">
                <label for="field-{{ $field }}">{{ \Illuminate\Support\Str::headline($field) }}</label>
            @if (str_ends_with($field, '_image'))
                <img class="section-editor-preview" src="{{ $siteContent->image($section, $field) }}" alt="Current image">
                <input id="field-{{ $field }}" name="fields[{{ $field }}]" type="file" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG or WebP, up to 4 MB.</small>
            @elseif (str_ends_with($field, '_url') || $field === 'contact_email')
                <input id="field-{{ $field }}" name="fields[{{ $field }}]" type="{{ $field === 'contact_email' ? 'email' : 'url' }}" value="{{ old('fields.'.$field, $siteContent->value($section, $field)) }}" @required($field !== 'download_url')>
                @if ($field === 'download_url')
                    <small>Leave blank to show the coming-soon message.</small>
                @endif
            @else
                <textarea id="field-{{ $field }}" name="fields[{{ $field }}]" rows="{{ strlen($default) > 100 ? 4 : 2 }}" maxlength="6000" required>{{ old('fields.'.$field, $siteContent->value($section, $field)) }}</textarea>
            @endif
            </div>
        @endforeach
        <div class="section-editor-actions">
            <button class="section-save-button" type="submit">Save changes</button>
            <a href="{{ route($definition['route']) }}#edit-{{ $section }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
