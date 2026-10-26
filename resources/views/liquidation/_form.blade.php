{{-- Blade output stays escaped; file paths, ownership, and workflow states are controller-only. --}}
<x-ui.form-errors />

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form" data-content-form>
    @csrf
    @if ($liquidation)
        @method('PATCH')
    @endif

    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>

    {{-- Keep each label, field, and error together for quick form scanning. --}}
    <div class="dashboard-form-field">
        <label for="liquidation-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="liquidation-title" name="title" @error('title') aria-invalid="true" aria-describedby="liquidation-title-error" @enderror maxlength="200" required value="{{ old('title', $liquidation?->title) }}">
        @error('title') <span id="liquidation-title-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="liquidation-amount">Amount (PHP)</label>
        <input id="liquidation-amount" name="amount" @error('amount') aria-invalid="true" aria-describedby="liquidation-amount-error" @enderror type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('amount', $liquidation?->amount) }}">
        @error('amount') <span id="liquidation-amount-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="liquidation-report-date">Report date</label>
        <input id="liquidation-report-date" name="report_date" @error('report_date') aria-invalid="true" aria-describedby="liquidation-report-date-error" @enderror type="date" value="{{ old('report_date', $liquidation?->report_date?->format('Y-m-d')) }}">
        @error('report_date') <span id="liquidation-report-date-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="dashboard-form-field">
        <label for="liquidation-description">Description</label>
        <textarea id="liquidation-description" name="description" @error('description') aria-invalid="true" aria-describedby="liquidation-description-error" @enderror rows="6">{{ old('description', $liquidation?->description) }}</textarea>
        <span class="profile-field-help">Maximum 300 words.</span>
        @error('description') <span id="liquidation-description-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    @if ($liquidation)
        <p>Current PDF: {{ $liquidation->original_name }} · <a href="{{ route('workspace.posts.liquidations.show', $liquidation) }}">View current file</a>. Leave empty to keep the current file.</p>
    @endif

    {{-- SECURITY: Server verifies finfo MIME and the %PDF header, then generates a private stored filename. --}}
    <div class="dashboard-form-field">
        <label for="liquidation-file">{{ $liquidation ? 'Replace PDF (optional)' : 'PDF file' }}@unless ($liquidation) <span class="post-form-required-mark" aria-hidden="true">*</span>@endunless</label>
        <input id="liquidation-file" name="file" @error('file') aria-invalid="true" aria-describedby="liquidation-file-error" @enderror type="file" accept=".pdf,application/pdf" @required(! $liquidation)>
        <span class="profile-field-help">PDF only; maximum {{ config('school.max_pdf_size_kb', 10240) }} KB.</span>
        <span id="liquidation-file-info" class="profile-field-help"></span>
        @error('file') <span id="liquidation-file-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.liquidations.index') }}">Back to submissions</a>
    </div>
</form>

{{-- Display selected name and file size before submission. --}}
@push('scripts')
    <script>
        document.getElementById('liquidation-file')?.addEventListener('change', event => {
            const file = event.target.files?.[0];
            const info = document.getElementById('liquidation-file-info');
            if (info) info.textContent = file ? `${file.name} · ${(file.size / 1024).toFixed(1)} KB` : '';
        });
    </script>
@endpush
