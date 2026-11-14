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
        $path = null;
        if ($upload) {
            try {
                $path = $upload->storeAs('profile-pictures', $user->getKey().'-'.Str::random(40).'.'.$upload->extension(), 'public');
            } catch (Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages(['picture' => 'The picture could not be saved. Please try again.']);
            }
            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages(['picture' => 'The picture could not be saved. Please try again.']);
            }
        }
        try {
            DB::transaction(function () use ($user, $path): void {
                $current = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $old = $current->profile_picture;
                $current->profile_picture = $path;
                if (! $current->save()) {
                    throw new \RuntimeException('The profile picture could not be saved.');
                }
                DB::afterCommit(fn () => $this->cleanup($old));
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
