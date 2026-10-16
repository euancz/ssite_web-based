{{-- Picture controls use separate endpoints so the required profile form remains optional and independent. --}}
<section class="dashboard-panel profile-form-section profile-picture-section">
    <h2>Profile photo</h2>
    <div class="profile-picture-summary">
        <div id="profile-picture-preview"><x-ui.avatar :user="$user" size="lg" /></div>
        <div class="profile-picture-identity">
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->email }}</span>
        </div>
    </div>
    <form method="POST" action="{{ route('profile.picture.update') }}" enctype="multipart/form-data" class="profile-picture-upload-form">
        {{-- SECURITY: Uploads are validated server-side and scoped to the authenticated account. --}}
        @csrf
        <div class="dashboard-form-field">
            <label for="profile-picture-input">Change photo</label>
            <input id="profile-picture-input" type="file" name="picture" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
            <span class="profile-field-help">JPG, PNG, or WebP · up to 2 MB</span>
            @error('picture') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">Update photo</button>
        </div>
    </form>
    @if ($user->avatarUrl())
        <form method="POST" action="{{ route('profile.picture.remove') }}" onsubmit="return confirm('Remove your profile picture?');" class="profile-picture-remove-form">
            {{-- SECURITY: Removal targets the signed-in account and requires a CSRF token. --}}
            @csrf
            @method('DELETE')
            <button type="submit" class="dashboard-button dashboard-button-secondary">Remove</button>
        </form>
    @endif
</section>

@push('scripts')
    <script>
        // Preview a selected local file before upload without changing the stored picture.
        document.getElementById('profile-picture-input')?.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) return;
            const preview = document.getElementById('profile-picture-preview');
            const image = document.createElement('img');
            image.alt = @json($user->name);
            image.loading = 'lazy';
            image.src = URL.createObjectURL(file);
            image.className = 'ui-avatar ui-avatar-lg ui-avatar-preview-image';
            preview.replaceChildren(image);
        });
    </script>
@endpush
