{{-- Blade escapes every displayed value; workflow and storage fields are never accepted from this form. --}}
@if ($errors->any())
    <div class="dashboard-error" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form">
    @csrf
    @if ($document)
        @method('PATCH')
    @endif

    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>

    {{-- Keep each label, field, and error together for quick form scanning. --}}
    <div class="dashboard-form-field">
        <label for="document-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="document-title" name="title" maxlength="200" required value="{{ old('title', $document?->title) }}">
        @error('title') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="document-category">Category</label>
        <input id="document-category" name="category" maxlength="100" value="{{ old('category', $document?->category) }}">
        @error('category') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="document-description">Description</label>
        <textarea id="document-description" name="description" rows="6">{{ old('description', $document?->description) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('description') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    @if ($document)
        <p>Current PDF: {{ $document->original_name }} · <a href="{{ route('documents.show', $document) }}">View current file</a>. Leave empty to keep the current file.</p>
    @endif

    {{-- SECURITY: The server checks the real MIME and %PDF header, then stores a generated filename privately. --}}
    <div class="dashboard-form-field">
        <label for="document-file">{{ $document ? 'Replace PDF (optional)' : 'PDF file' }}@unless ($document) <span class="post-form-required-mark" aria-hidden="true">*</span>@endunless</label>
        <input id="document-file" name="file" type="file" accept=".pdf,application/pdf" @required(! $document)>
        <span class="profile-field-help">PDF only; maximum {{ config('school.max_pdf_size_kb', 10240) }} KB.</span>
        <span id="document-file-info" class="profile-field-help"></span>
        @error('file') <span class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
    </div>
</form>

{{-- Show the selected client name and size without uploading or exposing a storage path. --}}
@push('scripts')
    <script>
        document.getElementById('document-file')?.addEventListener('change', event => {
            const file = event.target.files?.[0];
            const info = document.getElementById('document-file-info');
            if (info) info.textContent = file ? `${file.name} · ${(file.size / 1024).toFixed(1)} KB` : '';
        });
    </script>
@endpush
