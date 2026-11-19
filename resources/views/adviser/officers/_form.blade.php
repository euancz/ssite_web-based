{{-- SECURITY: This multipart form posts only validated snapshot fields to adviser-only routes. --}}
<section class="dashboard-panel">
    @if ($errors->any())<div class="dashboard-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="dashboard-form">
        @csrf @if ($method !== 'POST') @method($method) @endif
        <div class="dashboard-form-field"><label for="academic_year">Academic year</label><select id="academic_year" name="academic_year" required>@foreach ($years as $year)<option value="{{ $year }}" @selected(old('academic_year', $officer->academic_year) === $year)>{{ $year }}</option>@endforeach</select></div>
        <div class="dashboard-form-field"><label for="position">Position</label><select id="position" name="position" required><option value="">Choose position</option>@foreach ($positions as $position)<option value="{{ $position }}" @selected(old('position', $officer->position) === $position)>{{ $position }}</option>@endforeach</select></div>
        <div class="dashboard-form-field"><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $officer->name) }}" maxlength="100" required></div>
        <div class="dashboard-form-field" data-officer-user-picker>
            <label for="user_id">Link to a user (optional)</label>
            <select id="user_id" name="user_id">
                <option value="">No linked user</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}" @selected((string) old('user_id', $officer->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            <div class="officer-user-picker" hidden>
                <input id="officer-user-search" type="text" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="officer-user-results" aria-describedby="officer-user-help officer-user-status" autocomplete="off" placeholder="Search by name or email">
                <div id="officer-user-results" class="officer-user-results" role="listbox" aria-label="User accounts" hidden></div>
            </div>
            <small id="officer-user-help">Search by name or email, then select an account to link it.</small>
            <small id="officer-user-status" role="status" aria-live="polite"></small>
        </div>
        <div class="dashboard-form-field"><label for="photo">Officer photo (JPG, PNG, WebP; up to 2 MB)</label><input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
        <div class="dashboard-form-field"><label for="sort_order">Sort order</label><input id="sort_order" type="number" name="sort_order" min="0" max="255" value="{{ old('sort_order', $officer->sort_order ?? 0) }}" required></div>
        <div class="dashboard-actions"><button class="dashboard-button" type="submit">Save officer</button><a class="dashboard-button dashboard-button-secondary" href="{{ route('adviser.officers.index', ['academic_year' => old('academic_year', $officer->academic_year)]) }}">Cancel</a></div>
    </form>
</section>
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/officer-user-picker.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/officer-user-picker.js') }}" defer></script>
@endpush
