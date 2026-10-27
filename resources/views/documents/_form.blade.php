{{-- Blade escapes every displayed value; workflow and storage fields are never accepted from this form. --}}
<x-ui.form-errors />

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form" data-content-form>
    @csrf
    @if ($document)
        @method('PATCH')
    @endif

    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>

    {{-- Keep each label, field, and error together for quick form scanning. --}}
    <div class="dashboard-form-field">
        <label for="document-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="document-title" name="title" @error('title') aria-invalid="true" aria-describedby="document-title-error" @enderror maxlength="200" required value="{{ old('title', $draftValues['title'] ?? $document?->title) }}">
        @error('title') <span id="document-title-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="document-category">Category</label>
        <input id="document-category" name="category" @error('category') aria-invalid="true" aria-describedby="document-category-error" @enderror maxlength="100" value="{{ old('category', $draftValues['category'] ?? $document?->category) }}">
        @error('category') <span id="document-category-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    @if (\App\Support\DocumentAcademicYear::available())
        <div class="dashboard-form-field">
            <label for="document-academic-year">Academic year (optional)</label>
            <input id="document-academic-year" name="academic_year" type="text" maxlength="9" pattern="[0-9]{4}-[0-9]{4}"
                placeholder="{{ \App\Support\AcademicYear::current() }}" list="document-academic-year-suggestions"
                value="{{ old('academic_year', $draftValues['academic_year'] ?? $document?->academic_year) }}"
                aria-describedby="document-academic-year-help @error('academic_year') document-academic-year-error @enderror" @error('academic_year') aria-invalid="true" @enderror>
            <datalist id="document-academic-year-suggestions">@foreach (\App\Support\AcademicYear::options() as $year)<option value="{{ $year }}"></option>@endforeach</datalist>
            <span id="document-academic-year-help" class="profile-field-help">Use consecutive years, such as 2026-2027. Choose the year the document covers; leave blank if unknown.</span>
            @error('academic_year')<span id="document-academic-year-error" class="profile-field-error">{{ $message }}</span>@enderror
        </div>
    @endif

    <div class="dashboard-form-field">
        <label for="document-semester">Semester</label>
        <select id="document-semester" name="semester" @error('semester') aria-invalid="true" aria-describedby="document-semester-error" @enderror>
            <option value="">Not assigned</option>
            <option value="1" @selected((string) old('semester', $draftValues['semester'] ?? $document?->semester) === '1')>1st Semester</option>
            <option value="2" @selected((string) old('semester', $draftValues['semester'] ?? $document?->semester) === '2')>2nd Semester</option>
        </select>
        @error('semester') <span id="document-semester-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="document-description">Description</label>
        <textarea id="document-description" name="description" @error('description') aria-invalid="true" aria-describedby="document-description-error" @enderror rows="6">{{ old('description', $draftValues['description'] ?? $document?->description) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('description') <span id="document-description-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    @if ($document)
        <p>Current PDF: {{ $document->original_name }} · <a href="{{ route('workspace.posts.documents.show', $document) }}">View current file</a>. Leave empty to keep the current file.</p>
    @endif

    {{-- SECURITY: The server checks the real MIME and %PDF header, then stores a generated filename privately. --}}
    <div class="dashboard-form-field">
        <label for="document-file">{{ $document ? 'Replace PDF (optional)' : 'PDF file' }}@unless ($document) <span class="post-form-required-mark" aria-hidden="true">*</span>@endunless</label>
        <input id="document-file" name="file" @error('file') aria-invalid="true" aria-describedby="document-file-error" @enderror type="file" accept=".pdf,application/pdf" @required(! $document)>
        <span class="profile-field-help">PDF only; maximum {{ config('school.max_pdf_size_kb', 10240) }} KB.</span>
        <x-workspace.upload-status input-id="document-file" :max-kb="config('school.max_pdf_size_kb', 10240)" />
        @error('file') <span id="document-file-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <x-workspace.content-preview :fields="['category' => 'Category', 'semester' => 'Semester', 'academic_year' => 'Academic year']" />

    @unless ($document)<x-workspace.draft-controls module="documents" :draft="$draft ?? null" />@endunless

    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.documents.index') }}">Back to submissions</a>
    </div>
</form>
