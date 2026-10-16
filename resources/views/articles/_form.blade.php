{{-- This shared Article-only form keeps create and edit validation messages consistent. --}}
@if ($errors->any())
    <div class="dashboard-error" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- SECURITY: Only title, content, and a validated image are sent; workflow fields stay server-controlled. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form">
    @csrf
    @if ($article)
        @method('PATCH')
    @endif

    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>

    {{-- Title validation matches the articles table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="article-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="article-title" name="title" type="text" maxlength="200" required value="{{ old('title', $article?->title) }}">
        @error('title') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- Blade escapes article content on redisplay and the server validates it as required text. --}}
    <div class="dashboard-form-field">
        <label for="article-content">Content <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <textarea id="article-content" name="content" rows="14" required>{{ old('content', $article?->content) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('content') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- SECURITY: The server validates image bytes, extension, and size before storing a generated filename. --}}
    <div class="dashboard-form-field">
        <label for="article-image">{{ $article ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="article-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        @error('image') <span class="profile-field-error">{{ $message }}</span> @enderror
        @if ($article?->image)
            <img id="article-image-preview" class="article-form-preview" src="{{ $article->imageUrl() }}" alt="Current article image">
        @else
            <img id="article-image-preview" class="article-form-preview" src="" alt="Selected image preview" hidden>
        @endif
    </div>

    {{-- The form submits only editable fields and returns to the saved article. --}}
    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
    </div>
</form>

{{-- Preview local image selections without uploading them before form submission. --}}
@push('scripts')
    <script>
        document.getElementById('article-image')?.addEventListener('change', (event) => {
            const preview = document.getElementById('article-image-preview');
            const file = event.target.files?.[0];
            if (!preview || !file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    </script>
@endpush
