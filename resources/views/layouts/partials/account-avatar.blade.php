<div class="account-menu-avatar" role="presentation">
    <x-ui.avatar :user="$user" size="md" />
    @if ($showAccountPhotoDialog)
        <a href="{{ route('profile.edit') }}" class="account-photo-edit" role="menuitem"
            aria-label="Edit profile picture" aria-haspopup="dialog" aria-controls="account-photo-dialog"
            title="Edit profile picture" data-photo-edit>
            <x-icon name="pencil" size="sm" />
        </a>
    @endif
</div>
