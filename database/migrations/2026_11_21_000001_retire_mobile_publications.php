<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql' && DB::scalar("select to_regclass('storage.objects')") !== null) {
            DB::statement('drop policy if exists mobile_publication_files_read on storage.objects');
            DB::statement('drop policy if exists mobile_sponsor_logos_read on storage.objects');
        }
        Schema::dropIfExists('mobile_publications');
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('drop function if exists public.mobile_record_visible(text, bigint)');
            DB::statement('drop function if exists public.mobile_sponsor_visible(bigint)');
            DB::statement('drop function if exists public.mobile_reader_allowed()');
        }
    }

    public function down(): void
    {
        throw new LogicException('The retired publication feed requires its former publisher and access policies.');
    }
};

