<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProfilePictures
{
    public function replace(User $user, ?UploadedFile $upload): void
    {
        $this->savePicture($user, $upload);
    }

    public function complete(User $user, UploadedFile $upload, ?string $microsoftId): User
    {
        return $this->savePicture($user, $upload, completing: true, microsoftId: $microsoftId);
    }

    private function savePicture(
        User $user,
        ?UploadedFile $upload,
        bool $completing = false,
        ?string $microsoftId = null,
    ): User {
        $path = null;
        if ($upload) {
            try {
                $path = $upload->storeAs('profile-pictures', Str::random(40).'.'.$upload->extension(), 'public');
            } catch (Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages(['picture' => 'The picture could not be saved. Please try again.']);
            }
            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages(['picture' => 'The picture could not be saved. Please try again.']);
            }
        }
        try {
            return DB::transaction(function () use ($user, $path, $completing, $microsoftId): User {
                $current = $user->exists ? User::whereKey($user->getKey())->lockForUpdate()->firstOrFail() : $user;

                if ($microsoftId !== null) {
                    if ($current->microsoft_id && $current->microsoft_id !== $microsoftId) {
                        throw ValidationException::withMessages([
                            'account' => 'This email address is already linked to a different Microsoft account.',
                        ]);
                    }
                    $current->microsoft_id = $microsoftId;
                }

                // Discard this upload if another request already finished onboarding.
                $discarded = $path;
                if (! ($completing && $current->hasCompletedProfile() && $current->profile_picture)) {
                    $discarded = $current->profile_picture;
                    $current->profile_picture = $path;
                    if ($completing) {
                        $current->profile_completed_at = $current->profile_policies_accepted_at = now();
                    }
                }
                if (! $current->save()) {
                    throw new \RuntimeException('The profile picture could not be saved.');
                }
                DB::afterCommit(fn () => $this->cleanup($discarded));

                return $current;
            });
        } catch (Throwable $exception) {
            $this->cleanup($path);
            throw $exception;
        }
    }

    private function cleanup(?string $path): void
    {
        if (! $path || ! preg_match('#\Aprofile-pictures/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp)\z#iD', $path)) {
            return;
        }
        try {
            if (! Storage::disk('public')->delete($path)) {
                \Illuminate\Support\Facades\Log::warning('Profile picture cleanup needs a retry.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
