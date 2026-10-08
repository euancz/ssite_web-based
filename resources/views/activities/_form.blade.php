{{-- This Activity-only form keeps create and edit fields and validation messages consistent. --}}
@if ($errors->any())
    <div class="dashboard-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

{{-- SECURITY: Workflow fields are excluded; only validated Activity content reaches the request. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form">
    @csrf
    @if ($activity) @method('PATCH') @endif
    {{-- The title length matches the activities table. --}}
    <div class="dashboard-form-field">
        <label for="activity-title">Title</label>
        <input id="activity-title" name="title" type="text" maxlength="200" required value="{{ old('title', $activity?->title) }}">
        @error('title') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The activity description is escaped when redisplayed and when shown publicly. --}}
    <div class="dashboard-form-field">
        <label for="activity-description">Description</label>
        <textarea id="activity-description" name="description" rows="12" required>{{ old('description', $activity?->description) }}</textarea>
        @error('description') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The optional date maps to activities.activity_date. --}}
    <div class="dashboard-form-field">
        <label for="activity-date">Activity date</label>
        <input id="activity-date" name="activity_date" type="date" value="{{ old('activity_date', $activity?->activity_date?->format('Y-m-d')) }}">
        @error('activity_date') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Location is optional and follows the table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="activity-location">Location</label>
        <input id="activity-location" name="location" type="text" maxlength="200" value="{{ old('location', $activity?->location) }}">
        @error('location') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- SECURITY: Upload validation and filename generation happen on the server. --}}
    <div class="dashboard-form-field">
        <label for="activity-image">{{ $activity ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="activity-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        @error('image') <span class="profile-field-error">{{ $message }}</span> @enderror
        @if ($activity?->image)
            <img id="activity-image-preview" class="article-form-preview" src="{{ $activity->imageUrl() }}" alt="Current activity image">
        @else
            <img id="activity-image-preview" class="article-form-preview" src="" alt="Selected image preview" hidden>
        @endif
    </div>
    <div class="profile-form-actions"><button type="submit" class="dashboard-button">{{ $submitLabel }}</button></div>
</form>

{{-- Preview local image selections without uploading them before submission. --}}
@push('scripts')
<script>
    document.getElementById('activity-image')?.addEventListener('change', (event) => {
        const preview = document.getElementById('activity-image-preview');
        const file = event.target.files?.[0];
        if (!preview || !file) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
</script>
@endpush
