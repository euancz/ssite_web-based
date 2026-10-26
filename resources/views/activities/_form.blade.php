{{-- This Activity-only form keeps create and edit fields and validation messages consistent. --}}
<x-ui.form-errors />

{{-- SECURITY: Workflow fields are excluded; only validated Activity content reaches the request. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form" data-content-form>
    @csrf
    @if ($activity) @method('PATCH') @endif
    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>
    {{-- The title length matches the activities table. --}}
    <div class="dashboard-form-field">
        <label for="activity-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="activity-title" name="title" @error('title') aria-invalid="true" aria-describedby="activity-title-error" @enderror type="text" maxlength="200" required value="{{ old('title', $activity?->title) }}">
        @error('title') <span id="activity-title-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The activity description is escaped when redisplayed and when shown publicly. --}}
    <div class="dashboard-form-field">
        <label for="activity-description">Description <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <textarea id="activity-description" name="description" @error('description') aria-invalid="true" aria-describedby="activity-description-error" @enderror rows="12" required>{{ old('description', $activity?->description) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('description') <span id="activity-description-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The optional date maps to activities.activity_date. --}}
    <div class="dashboard-form-field">
        <label for="activity-date">Activity date</label>
        <input id="activity-date" name="activity_date" @error('activity_date') aria-invalid="true" aria-describedby="activity-date-error" @enderror type="date" value="{{ old('activity_date', $activity?->activity_date?->format('Y-m-d')) }}">
        @error('activity_date') <span id="activity-date-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Location is optional and follows the table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="activity-location">Location</label>
        <input id="activity-location" name="location" @error('location') aria-invalid="true" aria-describedby="activity-location-error" @enderror type="text" maxlength="200" value="{{ old('location', $activity?->location) }}">
        @error('location') <span id="activity-location-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- SECURITY: Upload validation and filename generation happen on the server. --}}
    <div class="dashboard-form-field">
        <label for="activity-image">{{ $activity ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="activity-image" data-image-preview="activity-image-preview" name="image" @error('image') aria-invalid="true" aria-describedby="activity-image-error" @enderror type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        @error('image') <span id="activity-image-error" class="profile-field-error">{{ $message }}</span> @enderror
        @if ($activity?->image)
            <img id="activity-image-preview" class="article-form-preview" src="{{ $activity->imageUrl() }}" alt="Current activity image">
        @else
            <span id="activity-image-preview-mount" hidden></span>
        @endif
    </div>
    <div class="profile-form-actions"><button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.activities.index') }}">Back to submissions</a></div>
</form>

{{-- Preview local image selections without uploading them before submission. --}}

