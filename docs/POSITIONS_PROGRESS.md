# Position-Based Permissions Progress

**Plan:** `docs/POSITIONS_PLAN.md` (approved 2026-10-15)

## Work status

| Work item | Status | Files / notes |
| --- | --- | --- |
| Batch 1 backend capabilities, policies, Gates, routes, notifications, assignment | done | Configured position map and canonical `User` methods; policies and route middleware now authorize by capability; role assignment is actor-scoped and transactional. |
| Batch 1 President auto-approval | done | All five store paths use configured auto-approval and stamp reviewer metadata. |
| Articles tabs, query scopes, review and management behavior | done | President review tabs and full archive scope; posting officers get their own tabs and archive; view-only roles see Published only. |
| Activities tabs, query scopes, review and management behavior | done | President review tabs and full archive scope; posting officers get their own tabs and archive; view-only roles see Published only. |
| Achievements tabs, query scopes, review and management behavior | done | President review tabs and full archive scope; posting officers get their own tabs and archive; view-only roles see Published only. |
| Documents tabs, query scopes, review and management behavior | done | President review tabs and full archive scope; Secretary gets own tabs and archive; view-only roles see Published only. |
| Liquidation tabs, query scopes, review and management behavior | done | President review tabs and full archive scope; Auditor gets own tabs and archive; view-only roles see Published only. |
| Manage menu, dashboards, users manager, no-President warning | done | Position-specific dashboards, count-only manager dashboard, superadmin adviser assignment/history, canonical position dropdown, President review queue, and identity position label. About roster controls remain suppressed. |
| Final system documentation and manual checklist | done | Updated `SYSTEM_OVERVIEW.md` and `WHERE_IS_WHAT.md`; manual verification checklist follows. |

## Batch 1 files changed

- `config/school.php`, `app/Models/User.php`: central position capabilities; positions grant abilities only when the account role is officer.
- `app/Policies/UserPolicy.php`, all five `app/Policies/*Policy.php`, `app/Providers/AppServiceProvider.php`: actor-specific role assignment and module policy/Gate enforcement.
- `routes/features.php`, `routes/adviser.php`, `routes/officer.php`: visible `can:` middleware for content and role actions; preserve URLs and names; roster routes are denied, review route is President-only.
- Five module controllers and `app/Http/Controllers/Adviser/ReviewController.php`: use `postsAutoApproved()`, notify configured reviewers, and describe the President review route accurately.
- `app/Support/PostNotificationRecipients.php`, `app/Notifications/PostSubmittedForReview.php`: resolve reviewers by `canReview()`; published broadcasts exclude author and actor and include all role types.
- `app/Http/Controllers/Adviser/UserController.php`: actor-constrained assignment validation, canonical position validation, President single-holder check under row locks, term/log changes in a transaction.
- `resources/views/pages/about.blade.php`, `resources/views/layouts/app.blade.php`: remove About roster controls and the Manage Officers link for adviser/superadmin.
- `docs/POSITIONS_PLAN.md`, this progress file: record approval and the confirmed role-only scope.

## Verification and issues

- `php artisan route:list --json`: 102 routes remain; route-key comparison found no added or missing URL/name/method. All 60 middleware differences match the approved families: five-module capabilities, denied roster routes, President review, officer-only dashboard, and policy-protected role update.
- PHP syntax checks passed for every changed PHP/config/route file.
- `php artisan view:clear`, `view:cache`, then `view:clear` all succeeded.
- Vite dependencies are absent (`node_modules` does not exist); no package install or network command was run.
- Tests were not run. The effective test DB is not confirmed safe and `.env` points to `ssitecms`.
- Database-backed user and pending counts remain unavailable; no data was read or changed.

## Log

- 2026-10-15 — User approved the plan and clarified adviser and superadmin may only assign roles. Batch 1 completed: capability config, model methods, policies, Gates, route middleware, President auto-approval, reviewer/broadcast audiences, and transactional role assignment are in place. About roster controls are suppressed and roster routes denied. Route inventory remains 102 entries; no schema, migration, seed, or data mutation was performed. PHP lint and Blade cache checks passed. Tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User approved Batch 1. Articles module completed: tabs now follow Article capability, President sees all archived Articles, posting officers see only their own archive, and view-only roles remain on Published. Article page controls and review copy reflect President review. No routes or data changed. PHP and Blade checks passed; tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User continued after Article review. Activities module completed: tabs now follow Activity capability, President sees all archived Activities, posting officers see only their own archive, and view-only roles remain on Published. Activity page controls reflect President management. No routes or data changed. PHP and Blade checks passed; tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User continued after Activities review. Achievements module completed: tabs now follow Achievement capability, President sees all archived Achievements, posting officers see only their own archive, and view-only roles remain on Published. Achievement page controls reflect President management. No routes or data changed. PHP and Blade checks passed; tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User continued after Documents review. Liquidations module completed: tabs now follow Liquidation capability, President sees all archived reports, Auditor sees only their own archive, and view-only roles remain on Published. Liquidation page controls reflect President management. No routes or data changed. PHP and Blade checks passed; tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User continued after Achievements review. Documents module completed: tabs now follow Document capability, President sees all archived Documents, Secretary sees only their own archive, and view-only roles remain on Published. Document page controls reflect President management. No routes or data changed. PHP and Blade checks passed; tests remain unrun because the safe-database rule is not satisfied.
- 2026-10-15 — User continued after Liquidations review. Batch 3 completed: menus show role-specific actions and canonical positions; adviser/superadmin dashboards expose only permitted counts and role data; officer dashboard is position-scoped; canonical officer position choices and actor-specific role options are shown in the user manager; President review queue now reads and acts on the five module tables. Updated `SYSTEM_OVERVIEW.md` and `WHERE_IS_WHAT.md`. PHP lint, Blade compilation, route inventory, and security-path review passed. Tests remain unrun because the safe-database rule is not satisfied.

## Manual verification checklist

- Sign in as President: account menu shows President position, Dashboard, Review Posts with pending count; dashboard shows per-module counts and recent pending items; review route can approve/reject; all five module tabs and archived rows are visible.
- Sign in as Secretary, Auditor, and Multimedia: dashboard lists only allowed modules and owned records; create links match the assigned position; rejected reasons appear only on that officer's dashboard.
- Sign in as Adviser: manager allows student/officer assignments with canonical position choices; no About roster controls or private post titles; pending totals appear; no-President warning appears when no President exists.
- Sign in as Superadmin: manager allows adviser assignment/revocation only; dashboard shows adviser assignments and optional role history; no private post content or About roster controls.
- Sign in as an unsupported officer and as a student: landing page is Home and content tabs remain Published only.
- Submit without a President: post remains pending and no review notification is sent; assign a President and confirm future review notifications target that President.
- Try invalid tabs, unauthorized post actions, self/protected role changes, duplicate President assignment, and another Multimedia member's record; requests should fall back or be denied by server policy.
