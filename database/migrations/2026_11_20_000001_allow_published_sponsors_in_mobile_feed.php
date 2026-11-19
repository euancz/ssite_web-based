<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $bucket = str_replace("'", "''", (string) config('filesystems.disks.mobile.bucket'));
        DB::unprepared(str_replace('__MOBILE_BUCKET__', $bucket, <<<'SQL'
            create function public.mobile_sponsor_visible(record_key bigint)
            returns boolean language sql stable security definer set search_path = '' as $$
                select public.mobile_reader_allowed()
                    and exists (
                        select 1 from public.sponsors
                        where sponsors.id = record_key and sponsors.is_active
                    );
            $$;

            revoke all on function public.mobile_sponsor_visible(bigint) from public, anon;
            grant execute on function public.mobile_sponsor_visible(bigint) to authenticated;

            create policy mobile_sponsors_read on public.mobile_publications for select to authenticated
            using (module = 'sponsors' and public.mobile_sponsor_visible(record_id));

            create policy mobile_sponsor_logos_read on storage.objects for select to authenticated
            using (
                bucket_id = '__MOBILE_BUCKET__' and public.mobile_reader_allowed()
                and exists (
                    select 1 from public.mobile_publications as publication
                    where publication.module = 'sponsors'
                        and public.mobile_sponsor_visible(publication.record_id)
                        and publication.payload->>'logo_path' = storage.objects.name
                )
            );
            SQL));
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                drop policy if exists mobile_sponsor_logos_read on storage.objects;
                drop policy if exists mobile_sponsors_read on public.mobile_publications;
                drop function if exists public.mobile_sponsor_visible(bigint);
                SQL);
        }
    }
};
