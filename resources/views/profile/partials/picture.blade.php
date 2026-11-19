@php($canRemove = ! $onboarding && filled($user->profile_picture))

<section class="dashboard-panel profile-picture-section profile-photo-panel" aria-label="Profile picture">
    <div class="profile-picture-summary">
        <div id="profile-picture-preview"><x-ui.avatar :user="$user" :fallback-name="$name" size="lg" /></div>
        @if ($onboarding)
            <div class="profile-picture-identity"><strong>{{ $name }}</strong></div>
        @endif
    </div>
    <form method="POST" action="{{ route($onboarding ? 'profile.complete.store' : 'profile.picture.update') }}" enctype="multipart/form-data" class="profile-picture-upload-form">
        @csrf
        <div class="dashboard-form-field">
            <label for="profile-picture-input">Choose a photo</label>
            <input id="profile-picture-input" type="file" name="picture" accept=".jpg,.jpeg,.png"
                aria-describedby="profile-picture-help @error('picture') profile-picture-error @enderror" @error('picture') aria-invalid="true" @enderror required>
            <span id="profile-picture-help" class="profile-field-help">JPG or PNG · up to 2 MB</span>
            @error('picture') <span id="profile-picture-error" class="profile-field-error" role="alert">{{ $message }}</span> @enderror
        </div>
        @if ($onboarding)
            <label class="profile-policy-consent">
                <input type="checkbox" name="accept_policies" value="1" @checked(old('accept_policies') === '1')
                    @error('accept_policies') aria-invalid="true" aria-describedby="profile-policy-error" @enderror required>
                <span>I agree to the <a href="{{ route('policies') }}#terms-of-service" target="_blank" rel="noopener">Terms of Service</a>
                    and <a href="{{ route('policies') }}#data-privacy" target="_blank" rel="noopener">Privacy Policy</a>.</span>
            </label>
            @error('accept_policies') <span id="profile-policy-error" class="profile-field-error" role="alert">{{ $message }}</span> @enderror
        @endif
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">{{ $onboarding ? 'Upload photo and continue' : 'Save photo' }}</button>
            @if ($onboarding)
                <button type="submit" form="profile-policy-decline" class="dashboard-button dashboard-button-secondary">Decline and sign out</button>
            @elseif ($canRemove)
                <button type="submit" form="profile-picture-remove" class="dashboard-button dashboard-button-secondary">Remove photo</button>
            @endif
        </div>
    </form>
    @if ($onboarding)
        <form method="POST" action="{{ route('logout') }}" id="profile-policy-decline" hidden>
            @csrf
        </form>
    @elseif ($canRemove)
        <form method="POST" action="{{ route('profile.picture.remove') }}" onsubmit="return confirm('Remove your profile picture?');" id="profile-picture-remove" hidden>
            @csrf
            @method('DELETE')
        </form>
    @endif
</section>

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('profile-picture-input');
            const preview = document.getElementById('profile-picture-preview');
            const original = preview.firstElementChild;
            let previewUrl;

            input.addEventListener('change', () => {
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                const file = input.files?.[0];
                if (!file) {
                    preview.replaceChildren(original);
                    return;
                }

                previewUrl = URL.createObjectURL(file);
                const image = document.createElement('img');
                image.alt = 'Selected profile photo';
                image.src = previewUrl;
                image.className = 'ui-avatar ui-avatar-lg ui-avatar-preview-image';
                preview.replaceChildren(image);
            });
        })();
    </script>
@endpush
