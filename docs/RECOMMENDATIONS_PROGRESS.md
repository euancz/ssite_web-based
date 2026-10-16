# Recommendations Progress

| ID | Status | Files changed | Notes |
|---|---|---|---|
| R-01 | done | `config/school.php`, five post controllers, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/SORT_FILTER_PROGRESS.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Safe defaults and schema-aware engagement sorts |
| R-02 | done | `app/Providers/AppServiceProvider.php`, `routes/auth.php`, `routes/features.php`, `routes/profile.php`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Throttle login and high-volume mutations |
| R-03 | done | `database/migrations/0001_01_01_000000_create_users_table.php`, `database/migrations/2026_10_06_020000_create_officer_terms_table.php`, `database/migrations/2026_10_16_000000_add_officer_term_user_year_unique.php`, `docs/RECOMMENDATIONS.md`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Aligned fresh user key type and officer-term uniqueness. No external post-table schema was changed. |
| R-04 | done | `phpunit.xml`, `tests/Unit/ContentAuthorizationTest.php`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Added focused checks for officer module permissions, review authority, ownership, private PDF access, and public visibility; PHPUnit forces SQLite :memory:. |
| R-05 | done | `docs/DEPLOYMENT_RUNBOOK.md`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Added host-neutral production, MySQL and file backup/restore, release, health, and logging steps. Restore rehearsal awaits an isolated host. |
| R-06 | done | `resources/views/errors/`, `public/css/errors.css`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Added standalone branded 403/404/419/500 pages with recovery links; 500 shows no exception details. |
| R-07 | done | `config/app.php`, `.env.example`, `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/DEPLOYMENT_RUNBOOK.md`, `docs/RECOMMENDATIONS_PROGRESS.md` | Application timezone now defaults to Asia/Manila; database rows are not rewritten. |
| R-08 | skipped | n/a | Owner chose not to add password recovery; login and mail settings remain unchanged. |
| R-09 | skipped | n/a | Owner chose not to replace the stock README. |
| R-10 | skipped | n/a | Owner chose to skip the browser and accessibility review. |

## Log

- 2026-10-16 — User approved R-01 through R-10. No implementation batch has started. Work will proceed one batch at a time after the user approves each posted batch plan with “go.”
- 2026-10-16 — R-01 implementation complete. Engagement flags default on per the owner's clarification; Published likes controls/sorting require both the likes table and the module counter column. PHP syntax checks, `view:clear` → `view:cache` → `view:clear`, and `git diff --check` passed. Compared with the 102-route BEFORE snapshot, the only delta remains the pre-existing `posts.like` route; this batch changed no routes. No tests, database queries, or migrations were run. Browser viewport, keyboard-only, JavaScript-off, and empty-state checks remain unverified because no browser was available.
- 2026-10-16 — Owner clarified likes and view counts are already working and should show; restored both config defaults to enabled while retaining per-module schema guards.
- 2026-10-16 — R-02 complete. Added account/IP login limits, user/IP limits for likes and content mutations, and tighter user/IP limits for uploads. Route paths and names are unchanged; middleware now includes the named limits on the approved routes. Compared with the 102-route BEFORE snapshot, the only path/name delta remains the pre-existing `posts.like` route. PHP syntax, Blade cache cycle, route comparison, and diff checks passed. No tests or database queries were run.
- 2026-10-16 — R-03 complete. The supplied users and officer-term structures confirmed an INT UNSIGNED custom user key and a unique user/year roster key. Fresh migrations now match both invariants, with a guarded additive migration for a pre-existing roster table. No external content-table schema was changed; the older schema snapshot is not treated as current. PHP syntax, Blade cache cycle, route comparison, and diff checks passed. No tests, database queries, or migrations were run.

- 2026-10-16 - R-04 complete. PHPUnit force-selects SQLite :memory: so tests do not inherit local MySQL settings. Added focused authorization tests for adviser role assignment, officer module permissions, President review, owner editing, publication visibility, and private PDF access. The focused file passed (5 tests, 27 assertions); PHP syntax, Blade cache cycle, and git diff --check passed. No migrations or route changes were made.

- 2026-10-16 - R-05 complete. Added a host-neutral runbook for production configuration, release steps, MySQL plus public/private storage backups, isolated restore, health checks, logs, and rollback cautions. The database and files were not backed up or changed; no migration or restore was run. A provider-specific rehearsal remains pending a non-production destination. Documentation diff checks passed.

- 2026-10-16 - R-06 complete. Added standalone branded 403, 404, 419, and 500 pages with a public-home recovery link; the 500 copy contains no exception details. Blade view clear/cache/clear passed, the Impeccable detector returned no findings, and git diff --check passed. No routes, SQL, or tests changed. Browser rendering and viewport screenshots were unavailable, so visual behavior remains unverified in a browser.

- 2026-10-16 - R-07 complete. Application timezone now uses `APP_TIMEZONE`, defaulting to `Asia/Manila`; the environment template and date-handling notes are updated. PHP syntax passed, `php artisan about --only=environment` reported Asia/Manila, and git diff --check passed. No tests, database queries, migrations, or route changes were made. Existing MySQL timestamp values were not changed; review older displays after deployment if they were recorded under another timezone.

- 2026-10-16 - Owner skipped R-08. No password-recovery routes, views, or mail configuration changes will be made.

- 2026-10-16 - Owner skipped R-09. The stock README remains unchanged.

- 2026-10-16 - Owner skipped R-10. No browser review, screenshots, or UI changes were made.
