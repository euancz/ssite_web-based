{{-- Blade output stays escaped; file paths, ownership, and workflow states are controller-only. --}}
@if ($errors->any())<div class="dashboard-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form">
    @csrf @if ($liquidation) @method('PATCH') @endif
    <div class="dashboard-form-field"><label for="liquidation-title">Title</label><input id="liquidation-title" name="title" maxlength="200" required value="{{ old('title', $liquidation?->title) }}">@error('title')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
    <div class="dashboard-form-field"><label for="liquidation-amount">Amount (PHP)</label><input id="liquidation-amount" name="amount" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('amount', $liquidation?->amount) }}">@error('amount')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
    <div class="dashboard-form-field"><label for="liquidation-report-date">Report date</label><input id="liquidation-report-date" name="report_date" type="date" value="{{ old('report_date', $liquidation?->report_date?->format('Y-m-d')) }}">@error('report_date')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
    <div class="dashboard-form-field"><label for="liquidation-description">Description</label><textarea id="liquidation-description" name="description" rows="6">{{ old('description', $liquidation?->description) }}</textarea>@error('description')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
    @if ($liquidation)<p>Current PDF: {{ $liquidation->original_name }} · <a href="{{ route('liquidations.view', $liquidation) }}">View current file</a>. Leave empty to keep the current file.</p>@endif
    {{-- SECURITY: Server verifies finfo MIME and the %PDF header, then generates a private stored filename. --}}
    <div class="dashboard-form-field"><label for="liquidation-file">{{ $liquidation ? 'Replace PDF (optional)' : 'PDF file' }}</label><input id="liquidation-file" name="file" type="file" accept=".pdf,application/pdf" @required(! $liquidation)><span class="profile-field-help">PDF only; maximum {{ config('school.max_pdf_size_kb', 10240) }} KB.</span><span id="liquidation-file-info" class="profile-field-help"></span>@error('file')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
    <div class="profile-form-actions"><button type="submit" class="dashboard-button">{{ $submitLabel }}</button></div>
</form>
{{-- Display selected name and file size before submission. --}}
@push('scripts')<script>document.getElementById('liquidation-file')?.addEventListener('change', event => { const file = event.target.files?.[0]; const info = document.getElementById('liquidation-file-info'); if (info) info.textContent = file ? `${file.name} · ${(file.size / 1024).toFixed(1)} KB` : ''; });</script>@endpush
