# Find the code to debug

Request flow: `routes/` -> `app/Http/Controllers/` -> requests, policies, models,
and services -> `resources/views/`.

| Problem or change | Start here |
| --- | --- |
| Landing or homepage | `routes/web.php`, `HomeController.php`, `resources/views/pages/` |
| Login or Microsoft callback | `routes/auth.php`, `app/Http/Controllers/Auth/` |
| Content, review actions, search, notifications | `routes/features.php`, matching feature controller |
| Adviser or officer pages | `routes/adviser.php`, `routes/officer.php`, matching controller subfolder |
| Profile | `routes/profile.php`, `app/Http/Controllers/Profile/`, `app/Http/Requests/Profile/` |
| Permissions | `app/Policies/`, `app/Http/Middleware/`, `app/Models/User.php` |
| Validation | `app/Http/Requests/`, then the controller method |
| Queries and shared helpers | `app/Models/`, `app/Support/`, `app/Services/` |
| Mobile HTTP endpoint declarations | `routes/api.php` |
| Mobile token exchange, identity, logout | `app/Http/Controllers/Mobile/AuthController.php` |
| Mobile account/workspace handoff | `app/Http/Controllers/Mobile/SessionController.php`, `app/Support/MobileAccountSession.php`, `app/Support/MobileWorkspace.php`; browser callbacks in `routes/web.php` |
| Mobile feeds, filters, file delivery | `app/Http/Controllers/Mobile/ContentController.php`, `app/Support/MobilePost.php` |
| Publishing to the shared app | `app/Services/MobilePublisher.php`, `app/Observers/MobilePublicationObserver.php`, `config/mobile.php` |
| Page shell, navigation, shared components | `resources/views/layouts/`, `resources/views/components/` |
| Page styling | `public/css/<feature>.css`; shared styles in `public/css/app.css` |
| Frontend behavior | `resources/js/`, `public/js/`; check layout script tags and `vite.config.js` |
| Runtime configuration | `config/`, local `.env`; use `.env.*.example` templates |
| Schema | `database/migrations/`, hand-maintained SQL in `database/sql/` |
| Automated coverage | `tests/Feature/`, `tests/Unit/`, `tests/browser/` |

See [WHERE_IS_WHAT.md](WHERE_IS_WHAT.md) for detailed feature ownership.

## Reproduce and trace

1. Note the URL, signed-in role, query parameters, and action.
2. Run `php artisan route:list --path=search` (replace `search` with your URL
   segment) to find its middleware and controller.
3. Inspect validation, authorization, then models/services. For display issues,
   follow the returned Blade view and its styles/scripts.
4. Check `storage/logs/laravel.log`; logs and uploads may contain private data.
5. Run the matching test before running the full suite.

```powershell
php artisan test --filter=Mobile
php artisan test --filter=ListingFilters
php artisan test
npm run build
```

PHP tests use in-memory SQLite through `phpunit.xml`. Do not reset the configured
development database or run demo seeders to debug a source change. Browser checks
in `tests/browser/` document their runtime requirements in the scripts.

## Folder conventions and Git

Keep Laravel's standard folders and feature-based view names. Routes declare
endpoints, controllers handle requests, services/support hold shared behavior,
requests validate input, and policies authorize actions.

`resources/css/` and `resources/js/` are Vite sources. `public/css/` and
`public/js/` also contain hand-maintained source loaded directly by Blade. Keep
both in Git. `public/build/` is generated; edit its source instead.

`docs/archive/` contains earlier plans/progress. `audit/` holds local audit
scripts/reviews; generated snapshots/reports are ignored. Storage and cache
`.gitignore` placeholders preserve required writable directories in checkouts.

Commit source, tests, environment examples, `composer.lock`, and
`package-lock.json`. Keep secrets, local MCP/tool state, uploads, logs, backups,
dependencies, and build output local. Ignore rules do not remove previous commits;
rotate any real secret that was previously published.
