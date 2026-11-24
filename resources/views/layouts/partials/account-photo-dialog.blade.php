@if ($showAccountPhotoDialog)
    @if (session('profile_picture_status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('profile_picture_status') }}</p>
    @endif
    <dialog id="account-photo-dialog" class="account-photo-dialog" aria-labelledby="account-photo-title">
        <h2 id="account-photo-title">Edit profile picture</h2>
        <button type="button" class="account-photo-close" aria-label="Close photo editor" data-photo-close>
            <x-icon name="x" />
        </button>
        @include('profile.partials.picture', ['onboarding' => false, 'name' => $user->name, 'inDialog' => true])
    </dialog>
@endif
