{{-- This Achievement-only form keeps create and edit fields and validation messages consistent. --}}
<x-ui.form-errors />

{{-- SECURITY: Workflow fields are excluded; only validated Achievement content reaches the request. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form" data-content-form>
    @csrf
    @if ($achievement) @method('PATCH') @endif
    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>
    {{-- The title length matches the achievements table. --}}
    <div class="dashboard-form-field">
        <label for="achievement-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="achievement-title" name="title" @error('title') aria-invalid="true" aria-describedby="achievement-title-error" @enderror type="text" maxlength="200" required value="{{ old('title', $draftValues['title'] ?? $achievement?->title) }}">
        @error('title') <span id="achievement-title-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The achievement description is escaped when redisplayed and when shown publicly. --}}
    <div class="dashboard-form-field">
        <label for="achievement-description">Description <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <textarea id="achievement-description" name="description" @error('description') aria-invalid="true" aria-describedby="achievement-description-error" @enderror rows="12" required>{{ old('description', $draftValues['description'] ?? $achievement?->description) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('description') <span id="achievement-description-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- The optional date maps to achievements.achievement_date. --}}
    <div class="dashboard-form-field">
        <label for="achievement-date">Achievement date</label>
        <input id="achievement-date" name="achievement_date" @error('achievement_date') aria-invalid="true" aria-describedby="achievement-date-error" @enderror type="date" value="{{ old('achievement_date', $draftValues['achievement_date'] ?? $achievement?->achievement_date?->format('Y-m-d')) }}">
        @error('achievement_date') <span id="achievement-date-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Awardee is optional and follows the table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="achievement-awardee">Awardee</label>
        <input id="achievement-awardee" name="awardee" @error('awardee') aria-invalid="true" aria-describedby="achievement-awardee-error" @enderror type="text" maxlength="200" value="{{ old('awardee', $draftValues['awardee'] ?? $achievement?->awardee) }}">
        @error('awardee') <span id="achievement-awardee-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- Category is optional and follows the table's 100-character limit. --}}
    <div class="dashboard-form-field">
        <label for="achievement-category">Category</label>
        <input id="achievement-category" name="category" @error('category') aria-invalid="true" aria-describedby="achievement-category-error" @enderror type="text" maxlength="100" value="{{ old('category', $draftValues['category'] ?? $achievement?->category) }}">
        @error('category') <span id="achievement-category-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>
    {{-- SECURITY: Upload validation and filename generation happen on the server. --}}
    <div class="dashboard-form-field">
        <label for="achievement-image">{{ $achievement ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="achievement-image" data-image-preview="achievement-image-preview" name="image" @error('image') aria-invalid="true" aria-describedby="achievement-image-error" @enderror type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        <x-workspace.upload-status input-id="achievement-image" :max-kb="2048" />
        @error('image') <span id="achievement-image-error" class="profile-field-error">{{ $message }}</span> @enderror
        @if ($achievement?->image)
            <img id="achievement-image-preview" class="article-form-preview" src="{{ $achievement->imageUrl() }}" alt="Current achievement image">
        @else
            <span id="achievement-image-preview-mount" hidden></span>
        @endif
    </div>
    <x-workspace.content-preview :fields="['achievement_date' => 'Achievement date', 'awardee' => 'Awardee', 'category' => 'Category']" />

    @unless ($achievement)<x-workspace.draft-controls module="achievements" :draft="$draft ?? null" />@endunless

    <div class="profile-form-actions"><button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.achievements.index') }}">Back to submissions</a></div>
</form>
