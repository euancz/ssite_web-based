<?php

namespace App\Console\Commands;

use App\Services\PostImages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class PrivatizePostImages extends Command
{
    protected $signature = 'media:privatize-post-images {--apply : Copy, verify, update references, and revoke public copies}';
    protected $description = 'Move legacy post images to authorized private storage; dry run by default';

    public function handle(): int
    {
        $count = 0;
        foreach (['articles', 'activities', 'achievements'] as $module) {
            if (! Schema::hasTable($module)) continue;
            $paths = DB::table($module)->whereNotNull('image')->where('image', '<>', '')
                ->where('image', 'not like', 'post-media/%')->distinct()->pluck('image');
            foreach ($paths as $source) {
                if (! PostImages::valid($source, $module) || ! Storage::disk('public')->exists($source)) {
                    $this->error('A legacy attachment is missing or has an unsupported path; no data was deleted.');
                    return self::FAILURE;
                }
                $count++;
                if (! $this->option('apply')) continue;
                $target = 'post-media/'.$source;
                $stream = Storage::disk('public')->readStream($source);
                if (! is_resource($stream)) return self::FAILURE;
                try {
                    if (! Storage::disk('local')->put($target, $stream)) return self::FAILURE;
                } finally {
                    fclose($stream);
                }
                if ($this->checksum('public', $source) !== $this->checksum('local', $target)) {
                    $this->error('Private copy verification failed; the original was retained.');
                    return self::FAILURE;
                }
                DB::transaction(function () use ($module, $source, $target): void {
                    $ids = DB::table($module)->where('image', $source)->lockForUpdate()->pluck('id');
                    DB::table($module)->whereIn('id', $ids)->where('image', $source)->update(['image' => $target]);
                });
            }
            if ($this->option('apply')) {
                // Also retry revocation after an earlier run committed references but failed to delete.
                foreach (Storage::disk('public')->files($module) as $source) {
                    $target = 'post-media/'.$source;
                    if (! PostImages::valid($source, $module) || DB::table($module)->where('image', $source)->exists()
                        || ! Storage::disk('local')->exists($target)) continue;
                    if ($this->checksum('public', $source) !== $this->checksum('local', $target)
                        || ! Storage::disk('public')->delete($source)) {
                        $this->error('A public copy could not be revoked; rerun to retry.');
                        return self::FAILURE;
                    }
                }
            }
        }
        $this->info($this->option('apply') ? "Privatized {$count} legacy attachment(s)." : "Dry run: {$count} legacy attachment(s) to privatize.");
        return self::SUCCESS;
    }

    private function checksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) throw new \RuntimeException('Attachment could not be verified.');
        try {
            $hash = hash_init('sha256');
            if (hash_update_stream($hash, $stream) === false) throw new \RuntimeException('Attachment could not be verified.');
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
