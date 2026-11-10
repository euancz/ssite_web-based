# Supabase setup

The Laravel backend uses Supabase PostgreSQL for records and Supabase Storage for file bytes.
Database columns retain relative paths. Images and avatars go in a public bucket; PDFs and
saved draft attachments go in a private bucket. Laravel still handles sign-in, permissions,
review workflows, and signed PDF routes. Flutter continues using the existing Laravel API.

## 1. Enable PostgreSQL in XAMPP

Open the php.ini shown by `php --ini` (normally C:/xampp/php/php.ini).
Uncomment `extension=pdo_pgsql` and `extension=pgsql`, then restart Apache.
Run `php -m` and verify `pdo_pgsql` appears. The current PHP installation does not enable it.

## 2. Create a Supabase project and buckets

In the Supabase dashboard, create a project and save its database password.
Open **Connect > Session pooler** and copy its exact host, port (5432), database and username.
Session pooling works on IPv4 networks; direct database connections may need IPv6.

In Storage, create:

- `ssite-public`: public bucket for post images, officer photos, and profile pictures.
- `ssite-private`: PRIVATE bucket for documents, liquidations, and drafts.

In Storage's S3 settings, generate an S3 access key and secret. Copy the region and endpoint.
These are different credentials from the database password and Supabase publishable/anon key.
The S3 credentials belong only in the Laravel .env; never put them in Flutter or frontend code.
No client upload policies are needed: Laravel uses server-side S3 credentials.

## 3. Enter credentials

Merge `.env.supabase.example` into your existing `.env`, replacing every YOUR_ placeholder.
Keep your current APP_KEY, Microsoft sign-in settings, APP_URL, and other application settings.
Do not replace the whole .env. Quote the database password. Remove any old DB_URL or update
it too, since a connection URL overrides individual DB_* settings. Use PostgreSQL's utf8 charset
if you previously set DB_CHARSET=utf8mb4. Keep MEDIA_STORAGE=local until both buckets are ready.

Run from SSITECMS:

```powershell
php artisan config:clear
php artisan migrate
```

Migrate creates tables on the configured database. It does NOT copy existing MySQL records.
The baseline migration supplies content tables previously created manually. The PostgreSQL
protection migration enables RLS on application tables so the Supabase Data API cannot expose
private app data. Use the project database owner connection; Laravel continues enforcing access.
Do not use migrate:fresh or migrate:refresh on a database containing records.

## 4. Existing MySQL records

Before changing DB_* settings, pause application writes and back up MySQL. Drain queued
jobs first. With .env still pointing to the current MySQL database, run:

```powershell
php artisan supabase:transfer-data export
```

The default file is `.tmp/supabase-data.jsonl` (ignored by Git). It contains private records
and password hashes; keep it outside public folders. Export refuses to overwrite an existing
file; use `--file=C:/a/private/location/new-export.jsonl` to choose another path.

After entering Supabase DB_* settings and running migrations on the empty target, run:

```powershell
php artisan supabase:transfer-data import
```

The importer preserves IDs, relative attachment paths, timestamps, and password hashes, and
resets PostgreSQL sequences. It refuses nonempty target app tables, unknown source columns,
and incomplete exports. Inserts run in one transaction and roll back on errors; review any
schema mismatch instead of discarding columns. It checks imported row counts. It leaves
migrations, sessions, caches, and job queues out of the transfer. Users sign in again.
Keep APP_KEY unchanged so encrypted application values remain readable. Compare source and
target counts and test sign-in and sample posts before allowing users back in. Custom source
columns or incompatible legacy values may need a reviewed mapping before import succeeds.
This command never deletes records from the old database. The export file remains afterward.

## 5. Copy existing attachments

Pause writes/uploads during the final copy. Once credentials and buckets are ready, set
MEDIA_STORAGE=supabase in .env and run:

```powershell
php artisan config:clear
php artisan supabase:copy-media --dry-run
php artisan supabase:copy-media
```

This copies storage/app/public to ssite-public and storage/app/private to ssite-private,
preserving paths and local originals. A repeated run skips existing objects without overwriting;
if an existing object has a different size it stops for manual review. Files in public/images
that are bundled assets remain served locally. Check uploads, image rendering, saved drafts,
and PDF previews (including seeking) after the copy. Verify a private bucket URL cannot be
opened without authorization. Restart queue workers with `php artisan queue:restart` if running.

## Rollback

Restore the previous DB_* values, set MEDIA_STORAGE=local, and run `php artisan config:clear`.
The original MySQL database and attachment files remain intact. Reconcile any writes made to
Supabase after cutover before rolling back; they are not automatically mirrored to MySQL.

## References

- [Supabase Laravel setup](https://supabase.com/docs/guides/getting-started/quickstarts/laravel)
- [Database connections](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [S3 credentials](https://supabase.com/docs/guides/storage/s3/authentication)
