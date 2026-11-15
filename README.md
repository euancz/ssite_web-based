# SSITECMS

Laravel CMS for SSITE content, reviews, documents, officer rosters, and the shared
mobile feed. The Flutter companion app is in the sibling `ssiteapp` repository.

Start with [the debugging map](docs/DEBUGGING.md) or
[the documentation index](docs/README.md).

## Source layout

| Folder | Responsibility |
| --- | --- |
| `routes/` | Web and mobile endpoint declarations |
| `app/Http/Controllers/` | Feature request handling; mobile API in `Mobile/` |
| `app/Http/Requests/`, `app/Policies/` | Validation and authorization |
| `app/Models/`, `app/Services/`, `app/Support/` | Data and shared behavior |
| `resources/views/` | Blade pages and shared components |
| `resources/js/`, `resources/css/` | Vite source |
| `public/js/`, `public/css/` | Hand-maintained assets loaded directly by Blade |
| `database/` | Migrations, seeders, source SQL |
| `tests/` | PHP unit/feature tests and browser checks |
| `docs/` | Current guides; earlier plans/progress in `archive/` |

## Local setup

Use PHP 8.2+, Composer, and Node.js. Install dependencies with `composer install`
and `npm ci`. For a new checkout, copy `.env.example` to `.env`, configure the
environment, then run `php artisan key:generate`. Preserve an existing environment
and application key.

Read [Supabase setup](docs/SUPABASE_SETUP.md) before applying migrations to an
existing database. Run `php artisan serve` and `npm run dev` in separate terminals
for local development. The XAMPP entry point is `public/`; see the
[deployment runbook](docs/DEPLOYMENT_RUNBOOK.md) for hosting details.

## Verification

Run `php artisan test`, `npm run build`, or narrow the PHP suite with
`php artisan test --filter=Mobile`. Tests use in-memory SQLite through
`phpunit.xml`. See [the debugging map](docs/DEBUGGING.md) for tracing routes, logs,
assets, and app publishing.

## Files kept local

Environment values, credentials, uploads, logs, database exports, local tool state,
dependencies, generated builds, and test reports are ignored. Keep environment
examples, lockfiles, source SQL, application assets, and tests tracked. Ignoring an
already tracked file does not remove it from prior Git history.
