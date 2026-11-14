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

    public function promoteDraft(string $source, Model $post): string
    {
        [$disk, $column, $directory] = $this->location($post);
        if ($column === 'image') {
            $disk = 'local';
            $directory = 'post-media/'.$post->getTable();
        }
        $name = Str::random(40).($column === 'file_path' ? '.pdf' : '.'.$this->extension($source));
        $target = $directory.'/'.$name;
        $stream = Storage::disk('local')->readStream($source);
        try {
            if (! is_resource($stream) || ! Storage::disk($disk)->put($target, $stream)) {
                throw ValidationException::withMessages([$column === 'image' ? 'image' : 'file' => 'The saved attachment could not be submitted. Please select it again.']);
            }
        } catch (Throwable $exception) {
            $this->deletePath($disk, $directory, $target);
            throw $exception;
        } finally {
            if (is_resource($stream)) fclose($stream);
        }

        return $target;
    }

    public function deleteDraft(string $path): void
    {
        if (preg_match('#\Adrafts/[0-9]+/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp|pdf)\z#iD', $path)) {
            try {
                Storage::disk('local')->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function save(Model $post, ?\Closure $afterSave = null): void
    {
        [$disk, $column, $directory] = $this->location($post);
        $oldPath = $post->getRawOriginal($column);
        $newPath = null;
        $replacement = false;
        try {
            DB::transaction(function () use ($post, $afterSave): void {
                $current = null;
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
                $afterSave?->__invoke();
                if ($post->isDirty() && ! $post->save()) {
                    throw new \RuntimeException('The attachment could not be saved.');
                }
                app(ContentVersions::class)->record($post, $current);
            });
            $newPath = $post->getAttribute($column);
            $replacement = $newPath && $newPath !== $oldPath;
        } catch (Throwable $exception) {
            $newPath = $post->getAttribute($column);
            $replacement = $newPath && $newPath !== $oldPath;
            if ($replacement) {
                [$newDisk, , $newDirectory] = $this->location($post);
                $this->deletePath($newDisk, $newDirectory, $newPath);
            }
            throw $exception;
        }
        if ($replacement && $oldPath) {
            $oldPost = clone $post;
            $oldPost->setAttribute($column, $oldPath);
            [$oldDisk, , $oldDirectory] = $this->location($oldPost);
            DB::afterCommit(fn () => $this->deletePath($oldDisk, $oldDirectory, $oldPath));
        }
    }

    public function delete(Model $post): void
    {
        [$disk, $column, $directory] = $this->location($post);
        DB::transaction(function () use ($post, $disk, $column, $directory): void {
            $current = $post->newQuery()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            [$disk, $column, $directory] = $this->location($current);
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
            'articles', 'activities', 'achievements' => [PostImages::disk($post->image), 'image',
                (str_starts_with((string) $post->image, 'post-media/') ? 'post-media/' : '').$post->getTable()],
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

    private function extension(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'bin';
    }
}
