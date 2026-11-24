@extends('layouts.app')
@section('title', $sponsor->exists ? 'Edit Sponsor' : 'Add Sponsor')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header :title="$sponsor->exists ? 'Edit Sponsor' : 'Add Sponsor'" subtitle="Maintain the partners and sponsors shown on the homepage." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Sponsor form">
        <x-ui.form-errors />

        <form method="POST" action="{{ $sponsor->exists ? route('workspace.sponsors.update', $sponsor) : route('workspace.sponsors.store') }}" enctype="multipart/form-data" class="profile-form">
            @csrf
            @if ($sponsor->exists)
                @method('PUT')
            @endif

            <div class="profile-form-grid">
                <div class="dashboard-form-field">
                    <label for="name">Sponsor name</label>
                    <input id="name" name="name" placeholder="e.g. SSITE Industry Partner" maxlength="255" value="{{ old('name', $sponsor->name) }}" required>
                </div>
                <div class="dashboard-form-field">
                    <label for="sort_order">Display order (lower numbers appear first)</label>
                    <input id="sort_order" type="number" name="sort_order" min="0" max="65535" value="{{ old('sort_order', $sponsor->sort_order) }}" required>
                </div>
            </div>

            <div class="dashboard-form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe how this sponsor supports SSITE" rows="4" maxlength="2000" required>{{ old('description', $sponsor->description) }}</textarea>
            </div>
            <div class="dashboard-form-field">
                <label for="website_url">Facebook page or website link (optional)</label>
                <input id="website_url" name="website_url" type="url" maxlength="2048" placeholder="https://www.facebook.com/YourPage" value="{{ old('website_url', $sponsor->website_url) }}">
            </div>

            @php($logoUrl = $sponsor->logoUrl())
            <div>
                <div class="dashboard-form-field">
                    <label for="logo">{{ $logoUrl ? 'Replace logo (optional)' : 'Logo (optional)' }}</label>
                    <x-workspace.attachment title="Attach a sponsor logo" help="JPG, JPEG or PNG up to 2 MB">
                        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png" data-image-preview="sponsor-logo-preview" @error('logo') aria-invalid="true" aria-describedby="sponsor-logo-error" @enderror>
                    </x-workspace.attachment>
                    <x-workspace.upload-status input-id="logo" :max-kb="2048" />
                    @error('logo') <span id="sponsor-logo-error" class="profile-field-error">{{ $message }}</span> @enderror
                    @if ($logoUrl)
                        <img id="sponsor-logo-preview" class="article-form-preview" src="{{ $logoUrl }}" alt="Sponsor logo preview">
                    @else
                        <span id="sponsor-logo-preview-mount" hidden></span>
                    @endif
                </div>
                @if ($logoUrl)
                    <label><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo</label>
                @endif
            </div>

            <div>
                <input type="hidden" name="is_active" value="0">
                <label><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $sponsor->is_active))> Show on the homepage</label>
            </div>

            <div class="profile-form-actions">
                <button class="dashboard-button" type="submit">Save sponsor</button>
                <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.sponsors.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</div>
@endsection
