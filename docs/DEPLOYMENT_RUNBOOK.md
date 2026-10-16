# Production Deployment and Restore Runbook

This guide is a host-neutral checklist for deploying SSITECMS and recovering it from a backup. Hosting-specific commands and a restore rehearsal still need to be confirmed with the selected provider. Never use production credentials or student data in a rehearsal.

## Production requirements

- Use a supported PHP version (8.2 or later) with the extensions required by Laravel 12 and the project dependencies. Install Composer dependencies from the committed `composer.lock`.
- Use MySQL compatible with the application's configured schema. Create a dedicated database and least-privilege application account; do not use the MySQL root account for web requests.
- Point the web server document root at the deployment's `public/` directory. Enable HTTPS and redirect HTTP to HTTPS.
- Keep the application code, `.env`, `storage/`, and `bootstrap/cache/` outside public web access. Only `public/` is exposed.
- Persist `storage/app/private` across releases. It contains private Document and Liquidation PDFs served through authorized application routes. Persist `storage/app/public` too; it contains public uploaded images such as officer photos.
- Provide write access to `storage/` and `bootstrap/cache/` for the PHP runtime account. Do not make the whole project writable by the web server.

## Environment configuration

Create `.env` on the host using a secure secret manager or host control panel. Do not commit it or paste secrets into deployment logs. Set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-host.example
APP_KEY=<generated-once-and-kept-secret>

DB_CONNECTION=mysql
DB_HOST=<private-database-host>
DB_PORT=3306
DB_DATABASE=<production-database>
DB_USERNAME=<least-privilege-application-user>
DB_PASSWORD=<secret>

FILESYSTEM_DISK=local
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Set the session, cache, queue, mail, Microsoft OAuth, and logging values to match the chosen host and enabled features. Do not use the example values in `.env.example` as production credentials. Generate `APP_KEY` once when creating a new environment and keep it with the encrypted environment backup; restoring the same deployment requires the same key. The application timezone defaults to `Asia/Manila`; set `APP_TIMEZONE` explicitly if a deployment needs another timezone.

## Release procedure

1. Take and verify a pre-deployment database and file backup using the procedure below.
2. Put the new release in a versioned release directory and keep the prior release available for rollback. Link the protected `.env`, persistent `storage/`, and any host-managed secrets into the release as appropriate.
3. Install locked production dependencies with `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Build frontend assets from the committed lockfile when the release changes frontend source; deploy the generated production assets with the release.
5. Review pending migrations against the target database before applying them. The five content tables are SQL-managed outside this repository's migrations. Do not assume `php artisan migrate --force` creates or upgrades those tables; compare their actual schemas and follow the approved schema procedure first.
6. Apply only reviewed Laravel migrations with `php artisan migrate --force`. Never run seeders against production unless a specific seeder has been reviewed and approved for that environment.
7. Run `php artisan storage:link` when the public storage symlink is absent. This link exposes only the configured public disk; private PDFs must stay in `storage/app/private` and use the authorized document/liquidation routes.
8. Run `php artisan optimize` after environment values and release files are in place. Confirm the PHP process can write to `storage/` and `bootstrap/cache/`.
9. Switch the web server to the release's `public/` directory. Check the health route/page, login, one public page, and authorized private-file access using a non-production account and a known test PDF.
10. Keep the previous release and pre-deployment backup until checks pass and the rollback window ends. If checks fail, switch code back and restore data only if the deployment changed the database or files in a way that requires it.

## Backup procedure

Schedule backups to a separate protected destination, not just another folder on the same host. Retain multiple dated copies according to the organization's retention policy. Encrypt backups in transit and at rest, restrict access, and record who can restore them.

Each recovery point must include:

- A consistent MySQL dump of the production database, including schema, indexes, and data.
- The full `storage/app/private/` directory, including Documents and Liquidation PDFs.
- The full `storage/app/public/` directory for uploaded public assets.
- A protected copy of the deployment `.env` and `APP_KEY`, stored separately from ordinary source backups.
- The release identifier or Git commit, backup timestamp, and the matching database/file backup identifiers.

For MySQL, use the host's managed backup facility or `mysqldump` with a restricted backup account. A typical command shape is:

```sh
mysqldump --single-transaction --routines --triggers \
  --host=<database-host> --user=<backup-user> --password \
  <database-name> > <protected-backup-path>/ssitecms-<timestamp>.sql
```

Use a tool that prompts for the password or a protected credential file; do not put passwords in shell history or command arguments. For non-transactional tables, coordinate a database lock or provider-consistent snapshot so database files and PDF storage represent a compatible point in time. Copy both storage directories to the protected destination, preserving nested paths and file permissions. Verify backup completion, file counts/size, and a checksum or archive test; a successful command alone does not prove recoverability.

## Restore procedure

Perform a restore rehearsal on an isolated non-production host and disposable database before relying on the process. Never restore over the live database as a test.

1. Select one recovery point and confirm its database dump, both storage archives, matching release identifier, and protected environment/APP_KEY record are present and readable.
2. Provision an isolated MySQL database and application account. Block public access while restoring; do not point the rehearsal app at production services.
3. Deploy the matching code release and restore its environment configuration securely. Confirm `APP_ENV=production`, `APP_DEBUG=false`, the isolated database host/name, and HTTPS URL.
4. Restore the MySQL dump into the isolated database. Restore `storage/app/private/` and `storage/app/public/` to the corresponding persistent paths, preserving file names and permissions.
5. Confirm the private disk still resolves to `storage/app/private` and the public symlink resolves only to `storage/app/public`. Do not create a public symlink for the private directory.
6. Clear stale cached configuration with `php artisan optimize:clear`, then rebuild caches with `php artisan optimize`. Run migrations only if the chosen recovery procedure explicitly requires migrations compatible with that backup; do not seed restored data.
7. Check the application health route/page, sign-in, public assets, and authorized PDF delivery. Verify an unauthenticated or unauthorized request cannot fetch the private test PDF. Review Laravel and web-server logs for restore errors.
8. Record elapsed recovery time, missing files, permission changes, and fixes. Update this runbook and repeat the rehearsal after material hosting or storage changes.

## Health checks and logs

- Use the Laravel health endpoint `/up` for a basic application process check. It does not prove database, Microsoft OAuth, mail, or private file storage is healthy.
- Also check a database-backed page, a public uploaded image, and authorized private PDF delivery during a release review.
- Review `storage/logs/laravel.log` when the configured channel writes to Laravel's local file, plus the hosting provider's PHP and web-server error logs. If logs go to a managed service, document the dashboard and access owner outside this repository.
- Keep `APP_DEBUG=false` in production. Use timestamps, release identifiers, and request context when reporting incidents; do not include passwords, OAuth secrets, session cookies, or student records in shared logs.

## Rollback notes

Switching back to the prior code release is safe only when its code is compatible with the current schema. If a migration or write changed data incompatibly, stop writes and follow the approved recovery plan using the matching database and storage recovery point. Do not delete a release, database, or backup until the owner has confirmed the recovery point and retention requirements.
