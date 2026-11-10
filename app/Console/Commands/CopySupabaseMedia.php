<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CopySupabaseMedia extends Command
{
    protected $signature = 'supabase:copy-media {--dry-run : List files without uploading}';
    protected $description = 'Copy existing local media into configured Supabase buckets, preserving paths and originals';

    public function handle(): int
    {
        if (config('filesystems.disks.local.driver') !== 's3' || config('filesystems.disks.public.driver') !== 's3') {
            $this->error('Set MEDIA_STORAGE=supabase and run php artisan config:clear first.');
            return self::FAILURE;
        }
        foreach (['public', 'private'] as $kind) {
            $disk = $kind === 'public' ? 'public' : 'local';
            $source = Storage::build(['driver' => 'local', 'root' => storage_path('app/'.$kind), 'throw' => true]);
            $target = Storage::disk($disk);
            foreach ($source->allFiles() as $path) {
                if (str_starts_with(basename($path), '.')) continue;
                if ($this->option('dry-run')) {
                    $this->line($kind.': '.$path);
                    continue;
                }
                // Never overwrite an existing object on a repeated run.
                if ($target->exists($path)) {
                    if ($target->size($path) !== $source->size($path)) {
                        $this->error('Different-size object already exists: '.$path);
                        return self::FAILURE;
                    }
                    $this->line('Already exists (not overwritten): '.$path);
                    continue;
                }
                $stream = $source->readStream($path);
                try {
                    if (! is_resource($stream) || ! $target->put($path, $stream, ['ContentType' => $source->mimeType($path)])) {
                        $this->error('Upload failed: '.$path);
                        return self::FAILURE;
                    }
                } finally {
                    if (is_resource($stream)) fclose($stream);
                }
                if ($target->size($path) !== $source->size($path)) {
                    $this->error('Uploaded size mismatch: '.$path);
                    return self::FAILURE;
                }
                $this->line('Copied: '.$path);
            }
        }
        $this->info($this->option('dry-run') ? 'Preview complete; no uploads performed.' : 'Copy complete. Local originals were retained.');
        return self::SUCCESS;
    }
}
