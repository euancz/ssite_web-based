@extends('layouts.app')
@section('title', $sponsor->exists ? 'Edit Sponsor' : 'Add Sponsor')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header :title="$sponsor->exists ? 'Edit Sponsor' : 'Add Sponsor'" subtitle="Maintain the partners and sponsors shown on the homepage." />
    <section class="dashboard-panel">
        @if ($errors->any())<div class="dashboard-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" action="{{ $sponsor->exists ? route('workspace.sponsors.update', $sponsor) : route('workspace.sponsors.store') }}" enctype="multipart/form-data" class="dashboard-form">
            @csrf
            @if ($sponsor->exists) @method('PUT') @endif
            <div class="dashboard-form-field"><label for="name">Sponsor name</label><input id="name" name="name" placeholder="e.g. SSITE Industry Partner" maxlength="255" value="{{ old('name', $sponsor->name) }}" required></div>
            <div class="dashboard-form-field"><label for="description">Description</label><textarea id="description" name="description" placeholder="Describe how this sponsor supports SSITE" rows="4" maxlength="2000" required>{{ old('description', $sponsor->description) }}</textarea></div>
            <div class="dashboard-form-field"><label for="website_url">Official website or social page (optional)</label><input id="website_url" name="website_url" type="url" maxlength="2048" placeholder="https://example.com" value="{{ old('website_url', $sponsor->website_url) }}"></div>
            @if ($sponsor->logoUrl())
                <img src="{{ $sponsor->logoUrl() }}" alt="Current logo for {{ $sponsor->name }}" width="100" height="100" style="object-fit:contain">
                <label><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo</label>
            @endif
            <div class="dashboard-form-field"><label for="logo">Logo (JPG, PNG or WebP, up to 2 MB; optional)</label><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"><small>A new upload replaces the current logo.</small></div>
            <div class="dashboard-form-field"><label for="sort_order">Display order (lower numbers appear first)</label><input id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $sponsor->sort_order) }}" required></div>
            <input type="hidden" name="is_active" value="0">
            <label><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $sponsor->is_active))> Show on the homepage</label>
            <div class="dashboard-actions"><button class="dashboard-button" type="submit">Save sponsor</button><a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.sponsors.index') }}">Cancel</a></div>
        </form>
    </section>
</div>
@endsection
