<?php

namespace App\Console\Commands;

use App\Services\MobilePublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SyncMobilePublications extends Command
{
    protected $signature = 'mobile:sync';
    protected $description = 'Publish approved website content and attachments to the shared Supabase app feed';

    public function handle(MobilePublisher $publisher): int
    {
        if (DB::getDriverName() !== 'pgsql' || ! config('mobile.sync_enabled')) {
            $this->error('Use the shared Supabase PostgreSQL database and set SSITE_MOBILE_SYNC=true first.');
            return self::FAILURE;
        }
        foreach (MobilePublisher::MODELS as $model) {
            $model::query()->chunkById(100, function ($posts) use ($publisher): void {
                foreach ($posts as $post) $publisher->publish($post);
            });
            $this->line('Synced '.$model);
        }
        $this->info('Mobile content is ready. Refresh the app to see updates.');
        return self::SUCCESS;
    }
}
