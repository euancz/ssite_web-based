{{-- This Achievement-only form keeps create and edit fields and validation messages consistent. --}}
@if ($errors->any())
    <div class="dashboard-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

{{-- SECURITY: Workflow fields are excluded; only validated Achievement content reaches the request. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form">
    @csrf
    @if ($achievement) @method('PATCH') @endif
    {{-- The title length matches the achievements table. --}}
    <div class="dashboard-form-field">
        <label for="achievement-title">Title</label>
        <input id="achievement-title" name="title" type="text" maxlength="200" required value="{{ old('title', $achievement?->title) }}">
        @error('title') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The achievement description is escaped when redisplayed and when shown publicly. --}}
    <div class="dashboard-form-field">
        <label for="achievement-description">Description</label>
        <textarea id="achievement-description" name="description" rows="12" required>{{ old('description', $achievement?->description) }}</textarea>
        @error('description') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The optional date maps to achievements.achievement_date. --}}
    <div class="dashboard-form-field">
        <label for="achievement-date">Achievement date</label>
        <input id="achievement-date" name="achievement_date" type="date" value="{{ old('achievement_date', $achievement?->achievement_date?->format('Y-m-d')) }}">
        @error('achievement_date') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Awardee is optional and follows the table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="achievement-awardee">Awardee</label>
        <input id="achievement-awardee" name="awardee" type="text" maxlength="200" value="{{ old('awardee', $achievement?->awardee) }}">
        @error('awardee') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Category is optional and follows the table's 100-character limit. --}}
    <div class="dashboard-form-field">
        <label for="achievement-category">Category</label>
        <input id="achievement-category" name="category" type="text" maxlength="100" value="{{ old('category', $achievement?->category) }}">
        @error('category') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- SECURITY: Upload validation and filename generation happen on the server. --}}
    <div class="dashboard-form-field">
        <label for="achievement-image">{{ $achievement ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="achievement-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        @error('image') <span class="profile-field-error">{{ $message }}</span> @enderror
        @if ($achievement?->image)
            <img id="achievement-image-preview" class="article-form-preview" src="{{ $achievement->imageUrl() }}" alt="Current achievement image">
        @else
            <img id="achievement-image-preview" class="article-form-preview" src="" alt="Selected image preview" hidden>
        @endif
    </div>
    <div class="profile-form-actions"><button type="submit" class="dashboard-button">{{ $submitLabel }}</button></div>
</form>

{{-- Preview local image selections without uploading them before submission. --}}
@push('scripts')
<script>
    document.getElementById('achievement-image')?.addEventListener('change', (event) => {
        const preview = document.getElementById('achievement-image-preview');
        const file = event.target.files?.[0];
        if (!preview || !file) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
</script>
@endpush
