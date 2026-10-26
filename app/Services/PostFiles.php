<?php

namespace App\Services;

use App\Support\PostEngagement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Coordinates database writes and file cleanup; files themselves cannot join a SQL transaction. */
class PostFiles
{
    public function store(UploadedFile $file, string $directory, string $disk, bool $pdf = false): string
    {
        $name = $pdf ? Str::random(40).'.pdf' : Str::uuid().'.'.$file->guessExtension();
        $path = $file->storeAs($directory, $name, $disk);
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([$pdf ? 'file' : 'image' => 'The upload could not be saved. Please try again.']);
        }

        return $path;
    }

    public function save(Model $post): void
    {
        [$disk, $column, $directory] = $this->location($post);
        $oldPath = $post->getRawOriginal($column);
        $newPath = $post->getAttribute($column);
        $replacement = $newPath && $newPath !== $oldPath;
        try {
            DB::transaction(function () use ($post): void {
                if ($post->exists) {
                    $current = $post->newQuery()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
                    // Do not overwrite a review or edit that completed after this request read the record.
                    foreach ($post->getRawOriginal() as $key => $value) {
                        if (in_array($key, ['view_count', 'like_count'], true)) {
                            continue;
                        }
                        if ($current->getRawOriginal($key) !== $value) {
                            throw ValidationException::withMessages(['content' => 'This post changed while it was being saved. Reload it and review the latest version.']);
                        }
                    }
                }
                if (! $post->save()) {
                    throw new \RuntimeException('The post could not be saved.');
                }
            });
        } catch (Throwable $exception) {
            if ($replacement) {
                $this->deletePath($disk, $directory, $newPath);
            }
            throw $exception;
        }
        if ($replacement && $oldPath) {
            DB::afterCommit(fn () => $this->deletePath($disk, $directory, $oldPath));
        }
    }

    public function delete(Model $post): void
    {
        [$disk, $column, $directory] = $this->location($post);
        DB::transaction(function () use ($post, $disk, $column, $directory): void {
            $current = $post->newQuery()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            $path = $current->getAttribute($column);
            app(PostEngagement::class)->deleteLikesForPost($current);
            if (! $current->delete()) {
                throw new \RuntimeException('The post could not be deleted.');
            }
            DB::afterCommit(fn () => $this->deletePath($disk, $directory, $path));
        });
    }

    private function location(Model $post): array
    {
        return match ($post->getTable()) {
            'articles', 'activities', 'achievements' => ['public', 'image', $post->getTable()],
            'documents' => ['local', 'file_path', 'documents'],
            'liquidations' => ['local', 'file_path', 'liquidation'],
            default => throw new \InvalidArgumentException('Unsupported post type.'),
        };
    }

    private function deletePath(string $disk, string $directory, ?string $path): void
    {
        if (! $path || ! preg_match('#\A'.preg_quote($directory, '#').'/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp|pdf)\z#iD', $path)) {
            return;
        }
        try {
            if (! Storage::disk($disk)->delete($path)) {
                Log::warning('Post file cleanup needs a retry.', ['disk' => $disk, 'directory' => $directory]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
