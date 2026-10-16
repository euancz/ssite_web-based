# CMS Review Recommendations

## Executive summary

Overall: the application has a coherent Laravel/Blade structure and thoughtful authorization for its core posting flow, but its deployment and schema history need tighter documentation before it is safe to hand off or deploy cheaply.

Strongest parts: module policies plus route middleware; private PDF delivery after authorization; position-based capabilities centralized in configuration.

Five biggest risks: engagement features default on despite their documented disabled state; no explicit throttles on password login or write-heavy endpoints; migrations do not reproduce the custom user key; permission/approval coverage is small; no deployment/backup runbook.

This is a code and repository review. No tests or database commands were run. The route list was inspected (100 routes); existing route snapshots are in `audit/`. No browser was available, so rendered layout and keyboard behavior remain unverified.

## Your top 10

1. R-01 — Make optional engagement safe by default and guard every schema-dependent sort.
2. R-02 — Add explicit rate limits to login and high-volume mutations.
3. R-03 — Match fresh-install custom key types and officer-term uniqueness.
4. R-04 — Add focused tests for the role/position matrix and approval workflow.
5. R-05 — Write a production deployment and restore runbook.
6. R-06 — Add branded, useful 403/404/419/500 pages.
7. R-07 — Use the Philippines timezone for application dates.
8. R-08 — Provide account recovery for password accounts.
9. R-09 — Replace the stock Laravel README with SSITE onboarding notes.
10. R-10 — Complete a browser-based mobile and accessibility pass before release.

## The full recommendation list

### A. Security and permissions

- **ID:** R-01
- **Title:** Keep engagement available while guarding every schema-dependent sort.
- **Priority:** P1
- **Evidence:** `config/school.php:4-8` defaults `enable_view_counts` and `enable_likes` to `true`; the comment says externally managed schema is required. `app/Http/Controllers/DocumentController.php:62-83` offers `liked` sorting when `post_likes` exists and orders by `like_count`. The checked-in `2026_10_15_000000_create_post_likes_table.php:12-21` creates `post_likes` but does not add a `like_count` column to Documents (or other post tables). `app/Support/PostEngagement.php:34-38` checks that column for detail-page state, but that check does not gate the list sort. [seen in code]
- **Problem:** A fresh migration can make the likes table available while the module `like_count` columns remain absent. That can enable a Published sort which queries a missing column unless each module checks its full schema.
- **Recommendation:** Keep the engagement flags enabled by default as the owner requested, while making every count/sort/control depend on the exact backing schema it uses, including `like_count` on each module. Keep the features inert for any module whose required column or table is absent, and document the schema requirements.
- **Benefit:** Optional functionality stays inert until configured, and a partial migration cannot break a public feed.
- **Effort:** S — config and a small guard change, plus doc correction.
- **Risk:** Medium — incomplete schema checks could expose a broken query or control on a module.
- **Needs from me:** None; the owner confirmed engagement is already working and should be visible. No SQL required.
- **Depends on:** none
- **Reversible:** Yes — restore the prior config values and guards if the schema is confirmed complete.
- **Alternative:** Disable engagement separately per module until its schema is ready.

- **ID:** R-02
- **Title:** Throttle password login and high-volume write endpoints.
- **Priority:** P1
- **Evidence:** `routes/auth.php:12` defines password login without `throttle`; `routes/features.php:41-44` defines `posts.like` without it; the only visible explicit throttle is the search group at `routes/features.php:17`. Profile picture and post/PDF write routes are in `routes/profile.php:23-26` and `routes/features.php:64-115` without explicit limits. The PDF cap is 10 MB at `config/school.php:65`. [seen in code]
- **Problem:** Repeated login attempts and repeated uploads or like toggles are not bounded at the route level. This can expose student accounts to password guessing and consume storage or request capacity on low-cost hosting.
- **Recommendation:** Add a named login limiter keyed by normalized account and IP, with a user-readable cooldown. Add modest authenticated limits to likes and upload actions; keep normal browsing and post reads unrestricted. Return a friendly validation or 429 response that preserves the current Blade flow.
- **Benefit:** Reduces credential guessing and resource exhaustion without changing role permissions.
- **Effort:** M — define/test limiter keys and attach them to the relevant routes.
- **Risk:** Medium — shared school Wi-Fi IPs can affect students if limits are too strict.
- **Needs from me:** None; choose conservative limits during implementation.
- **Depends on:** none
- **Reversible:** Yes — remove the limiter middleware and definitions.
- **Alternative:** Apply login throttling first and defer upload limits until production storage is selected.

### B. Data and database

- **ID:** R-03
- **Title:** Match fresh-install custom key types and officer-term uniqueness.
- **Priority:** P1
- **Evidence:** `database/migrations/0001_01_01_000000_create_users_table.php:15` creates Laravel's `BIGINT` `id`, and `2026_10_05_010000_prepare_users_for_profile_completion.php:39-43` renames it without changing its type. `database/migrations/2026_10_06_020000_create_officer_terms_table.php:21,29` uses `INT UNSIGNED` for `officer_terms.user_id` and references `users.user_id`; the owner-provided users screenshot shows `user_id` as `INT UNSIGNED`. The officer-term screenshot and existing schema-only audit show a unique `(user_id, academic_year)` key, but the create migration does not define it. [seen in code]
- **Problem:** A fresh MySQL install can fail when creating the officer-term foreign key because the renamed user key remains `BIGINT UNSIGNED` while the referencing key is `INT UNSIGNED`. A fresh officer roster table also misses the composite uniqueness rule used by `updateOrCreate`, allowing duplicate user/year rows under concurrency.
- **Recommendation:** Make fresh installs create `users.user_id` as `INT UNSIGNED` and keep the session table's nullable user key type aligned. Add the officer-term composite unique key to fresh creation and an additive, duplicate-guarded migration for an existing table that lacks it. The five SQL-managed post-table structures remain external; compare their current structure before proposing any schema changes to them.
- **Benefit:** New environments and future maintainers can build a compatible database without guesswork.
- **Effort:** L — reconcile the legacy schema and check all five content modules.
- **Risk:** High — schema edits can break existing data or deployment assumptions if applied to the wrong database.
- **Needs from me:** Current schema-only exports for Articles, Activities, Achievements, Documents, and Liquidation before any changes to those external tables; no row data or personal data is needed.
- **Depends on:** none
- **Reversible:** No for an applied schema change; keep implementation as a new migration or owner-run SQL with a tested backup/restore path.
- **Alternative:** Document the current manual SQL process and a schema version checklist without changing external post tables.

### C. Performance

No high-impact performance defect was verified at the stated scale. Public feeds eager-load authors and paginate; search is bounded and paginated; the app has no need for a search engine or cache layer yet. Revisit database indexes only after the actual schema and query plan can be inspected safely.

### D. UX and visual design

- **ID:** R-06
- **Title:** Add branded error pages for common failures.
- **Priority:** P2
- **Evidence:** No `resources/views/errors/` directory exists. The application has shared Blade layouts and custom flash/error styles, but framework-level 403, 404, 419, and 500 responses have no project-specific views. [seen in code]
- **Problem:** A student who follows a stale link or loses a form session may receive a generic framework error with no clear way back to the public home or login flow.
- **Recommendation:** Add compact branded pages for 403, 404, 419, and 500 using the existing light-blue/dark-blue identity and shared layout only where safe. Give each page a plain explanation and a working route back; keep the 500 page free of exception details.
- **Benefit:** Errors feel consistent and help phone users recover without exposing internals.
- **Effort:** M — four small views plus a quick response check.
- **Risk:** Low — custom views can accidentally hide useful developer details if environment handling is changed; retain Laravel logging.
- **Needs from me:** None.
- **Depends on:** none
- **Reversible:** Yes — remove the custom views to restore framework defaults.
- **Alternative:** Add only 404 and 419 pages first.

### E. Accessibility and mobile

- **ID:** R-10
- **Title:** Complete a browser-based mobile and keyboard review before release.
- **Priority:** P1
- **Evidence:** Existing progress notes say no browser or automation was available and specifically leave responsive widths, keyboard-only navigation, JavaScript-off behavior, and rendered empty states unverified (`docs/SORT_FILTER_PROGRESS.md:14-24, 32-42`). [seen in code]
- **Problem:** The majority of users are on phones, but source inspection cannot establish whether sticky navigation, dropdowns, tap targets, focus order, zoom, or page overflow work in a real browser.
- **Recommendation:** Inspect public pages at 320, 768, and 1440 px, then test 200% zoom, keyboard-only navigation, reduced motion, and forms with JavaScript disabled. For login-only pages, use owner-provided screenshots as requested. Record only reproducible defects and preserve the established brand.
- **Benefit:** Finds practical barriers for students before they rely on the CMS during an event or approval deadline.
- **Effort:** M — a bounded pass across representative feed, form, and dashboard pages.
- **Risk:** Low — review only; any proposed UI changes can be approved separately.
- **Needs from me:** Screenshots for login-only pages; browser access to the running site for the rest.
- **Depends on:** none
- **Reversible:** Yes — this is verification only; resulting changes remain separate approvals.
- **Alternative:** Start with the home, one feed, one form, and one dashboard at phone and desktop widths.

### F. Code quality and simplicity

- **ID:** R-09
- **Title:** Replace the stock README with student-friendly SSITE onboarding.
- **Priority:** P2
- **Evidence:** `README.md:1-65` is the unmodified Laravel starter guide. The project’s useful system map and conventions live in `docs/SYSTEM_OVERVIEW.md` and `docs/WHERE_IS_WHAT.md`. [seen in code]
- **Problem:** A student taking over the codebase starts with generic Laravel links and no setup, schema, role, or asset guidance.
- **Recommendation:** Make the README a short project entry point: prerequisites, local setup, the fact that the app expects a custom `user_id` schema, where role and permission rules live, how private files are stored, and links to the existing maps. Keep detailed policy documentation in the current docs.
- **Benefit:** Shortens handover and reduces risky trial-and-error for the next student developer.
- **Effort:** S — edit one markdown file with already documented project facts.
- **Risk:** Low — inaccurate setup commands could confuse onboarding, so verify each command against the current repository.
- **Needs from me:** Confirm the intended local database setup if it differs from the current `.env.example`.
- **Depends on:** R-03
- **Reversible:** Yes — restore the prior README.
- **Alternative:** Add a brief README link to the two existing docs and defer full onboarding.

### G. Features and content workflow

- **ID:** R-08
- **Title:** Add password-account recovery if password login remains supported.
- **Priority:** P2
- **Evidence:** `routes/auth.php:9-18` exposes password login and Microsoft login, but contains no password-reset routes. The stock password-reset token table is created in `database/migrations/0001_01_01_000000_create_users_table.php:24-28`. The configured example mailer is `log` in `.env.example:50`. [seen in code]
- **Problem:** A student with a local password account has no self-service recovery path if they forget it or can no longer sign in through Microsoft.
- **Recommendation:** First confirm that password accounts are part of the supported user experience. If they are, implement Laravel’s standard reset flow with generic responses and a real, tested mail provider; never disclose whether an email belongs to an account. If Microsoft-only access is the intended policy, remove or clearly de-emphasize password login in a separately approved change.
- **Benefit:** Reduces avoidable account lockouts while preserving privacy.
- **Effort:** M — standard flow is modest, but mail delivery configuration and verification are required.
- **Risk:** Medium — poor mail setup can send users into a dead-end recovery flow.
- **Needs from me:** Decide whether password login is a supported sign-in method and provide mail configuration during implementation; no secret values in this report.
- **Depends on:** none
- **Reversible:** Yes — routes and UI can be removed; issued reset tokens expire.
- **Alternative:** Direct students to Microsoft account recovery and explicitly document that local passwords are unsupported.

### H. Operations and deployment

- **ID:** R-05
- **Title:** Write a production deployment and restore runbook.
- **Priority:** P1
- **Evidence:** `.env.example:2-4` is a local template with `APP_DEBUG=true`; `config/app.php` correctly defaults debug to false, but no production deployment or restore guide is present in the reviewed docs. The default local disk is `storage/app/private` (`config/filesystems.php:33-38`), which contains private PDFs and is not part of the public storage link. `.env` is not tracked, and the tracked-file scan found no matching committed key/secret assignments. [seen in code]
- **Problem:** A low-cost host can lose private PDFs or reveal exception pages if deployment settings, persistent storage, and backups are not handled deliberately. A database-only backup would omit those files.
- **Recommendation:** Document production environment requirements (`APP_ENV=production`, `APP_DEBUG=false`, HTTPS/session cookie settings), one-time cache/storage steps, writable directories, and a tested backup/restore procedure covering both MySQL and `storage/app/private`. Include a simple health check and identify where Laravel logs are reviewed. Keep real values out of the repository.
- **Benefit:** Makes deployment repeatable and gives the organization a recovery path after host expiry or storage loss.
- **Effort:** M — documentation plus one restore rehearsal on a non-production copy.
- **Risk:** Medium — an incomplete backup procedure can create false confidence; validate restoration before relying on it.
- **Needs from me:** Hosting choice and a non-production destination for a restore rehearsal.
- **Depends on:** R-03
- **Reversible:** Yes — documentation can be revised; the rehearsal must use an isolated copy.
- **Alternative:** At minimum, schedule owner-managed backups for the database and private storage and record where they are kept.

### I. Docs and testing

- **ID:** R-04
- **Title:** Add tests for the role/position matrix and approval lifecycle.
- **Priority:** P1
- **Evidence:** `tests/Feature/ExampleTest.php` covers login/profile gates, a few role routes, and mass assignment, but has no module-level approval, rejection, archive, restore, ownership, or private PDF authorization cases. `phpunit.xml:21-26` sets testing mode but leaves SQLite and `:memory:` commented out; `.env` has `DB_DATABASE=ssitecms`, and no `.env.testing` exists. [seen in code]
- **Problem:** The most important access rules can regress unnoticed, and the current test configuration does not establish a database separate from the live development database.
- **Recommendation:** Configure an isolated SQLite test database (or another clearly separate disposable database) before running tests. Then cover each position’s module capability, President review, owner-only management, visibility of pending/rejected/archived rows, and authorized private PDF delivery. Use factories and fixtures; do not copy production student records.
- **Benefit:** Makes future changes safer and gives the student developer executable examples of the authorization model.
- **Effort:** L — the role matrix crosses five modules and several workflow states.
- **Risk:** Medium — incorrect test isolation could write to `ssitecms`; verify the effective test connection before any test run.
- **Needs from me:** Approve/confirm an isolated test database configuration before tests are run. No SQL or current test execution was performed.
- **Depends on:** R-03
- **Reversible:** Yes — tests and test-only configuration can be removed without changing production data.
- **Alternative:** Start with focused policy tests for President, Secretary, Auditor, and Multimedia, then add workflow tests in a second step.

- **ID:** R-07
- **Title:** Set the application timezone to the Philippines.
- **Priority:** P2
- **Evidence:** `config/app.php:68` hard-codes `UTC`; `config/school.php:10-11` calculates academic-year rollover by month, and controllers use Laravel’s `now()` for dates and timestamps. [seen in code]
- **Problem:** During the first hours of a Philippine calendar date, UTC can still be on the previous date. Date-based UI and the June academic-year boundary can therefore disagree with local expectations.
- **Recommendation:** Set the app timezone to `Asia/Manila`, preferably through an explicit environment setting with that default. Review any date-only comparisons and display formats for the intended local time; leave database timestamps in the existing storage convention if changing it would affect historical records.
- **Benefit:** Calendar dates and academic-year boundaries match the users’ local timezone.
- **Effort:** S — configuration and focused date-boundary verification.
- **Risk:** Medium — rendered timestamps will shift, so check existing date displays before deployment.
- **Needs from me:** None; confirm whether historical timestamps are intended to display in Philippine time.
- **Depends on:** none
- **Reversible:** Yes — restore `UTC` and the prior environment setting.
- **Alternative:** Keep stored timestamps in UTC and convert only date display and academic-year calculations to `Asia/Manila`.

## Quick wins

- R-01 — High impact, effort S.

## Big bets

- **R-03 — Schema source of truth.** For: it prevents fresh installs and future officers from discovering custom-key incompatibilities only after deployment. Against: the real MySQL schema may contain undocumented choices, and reconciling it safely takes time and an owner-provided schema-only export.
- **R-04 — Permission/workflow test suite.** For: authorization is the foundation of private student information and unpublished content. Against: comprehensive coverage spans five similar modules and requires a safe, isolated database setup first.
- **R-05 — Deployment and restore runbook.** For: losing private liquidation PDFs or a student database could be difficult to recover from on an expiring free tier. Against: the correct backup and persistence instructions depend on the final host and storage service.

## Not recommended

- React or Inertia rewrite: Blade already fits the server-rendered workflows and is easier for a student maintainer to learn.
- Microservices: five related content modules and a few hundred users do not justify distributed deployment or duplicated authorization.
- Search engine or real-time sockets: bounded LIKE search and synchronous notifications fit the current size and feature set.
- Heavy admin package: the existing role rules are organization-specific; replacing them would add framework surface without reducing the core complexity.
- Cache the navigation counts now: it adds invalidation risk for a small user base, while the measured code already groups notification counts and only asks for pending totals for reviewers.
- Rewrite the visual identity: the existing brand is specific and consistently documented; review defects within it.

## Decisions to revisit

- Optional likes/view counts now default on at the owner's direction. The likes migration does not create the per-module `like_count` columns, so R-01 retains a per-module schema guard before showing controls or running likes sorting.

## Already planned

- Feed sort/filter controls and removal of per-feed search: completed in `docs/SORT_FILTER_PROGRESS.md` (Batches 1–3).
- Optional likes and view counts with user privacy and post cleanup: implemented in `docs/SORT_FILTER_PROGRESS.md` (Batch 4), with the schema/default mismatch raised separately as R-01.
- Position-based roles, post approval, filters, shared feed components, and navigation behavior: already described in `docs/SYSTEM_OVERVIEW.md`, `docs/FEED_DESIGN.md`, `docs/POSITIONS_PLAN.md`, `docs/POSITIONS_PROGRESS.md`, and `docs/SORT_FILTER_PLAN.md`.
- No separate `DESIGN_DIRECTION.md`, `DESIGN_PROGRESS.md`, or `ROLES_AND_LAYOUT_PLAN.md` was present in `docs/` at review time.

## Suggested sequence

1. **Phase 1 — Stabilize and secure:** R-01 and R-02 first; resolve R-03 and establish a safe test database for R-04 before any tests or schema work. R-05 follows once the host is chosen. No SQL is required for R-01/R-02; R-03 needs an owner-provided schema-only export; R-04 needs an isolated test database.
2. **Phase 2 - Polish and UX:** R-06 and R-07. R-10 was skipped at the owner's direction.
3. **Phase 3 - Growth and handover:** R-08 and R-09 were skipped at the owner's direction.

## Approval table

| ID | Title | Priority | Effort | Risk | Decision |
|---|---|---:|---:|---|---|
| R-01 | Safe defaults and schema-aware engagement sorts | P1 | S | Medium | Approve |
| R-02 | Throttle login and high-volume mutations | P1 | M | Medium | Approve |
| R-03 | Match fresh schema types and officer-term uniqueness | P1 | L | High | Approve |
| R-04 | Test permissions and approval lifecycle | P1 | L | Medium | Approve |
| R-05 | Production deployment and restore runbook | P1 | M | Medium | Approve |
| R-06 | Branded error pages | P2 | M | Low | Approve |
| R-07 | Philippines timezone | P2 | S | Medium | Approve |
| R-08 | Password-account recovery | P2 | M | Medium | Skip |
| R-09 | SSITE onboarding README | P2 | S | Low | Skip |
| R-10 | Browser mobile and accessibility review | P1 | M | Low | Skip |

The owner skipped R-08 and R-09; no password-recovery flow or README replacement will be added in this review. The owner also skipped R-10; no browser review is planned.

## Limits of this review

- Login-only pages were not inspected in a browser; no screenshots were provided.
- No browser tool or running app was available for guest viewport, keyboard, zoom, reduced-motion, JavaScript-off, or rendered-state checks.
- No tests were run. `phpunit.xml` does not enable SQLite, `.env.testing` is absent, and `.env` points at `ssitecms`; there is no verified safe test database. The test command must wait until test isolation is confirmed.
- No database queries were run. Indexes, live nullability, live foreign keys, and current row volumes remain unverified; this review used checked-in schema and existing documentation only.
- Production settings, host persistence, backup policy, and mail delivery cannot be verified until hosting is selected.
- `.env` is not tracked. A tracked-file search for common app/database/Microsoft/AWS secret assignments returned no matches; this is not a full historical secret audit.
- The Impeccable detector flagged empty `src` preview elements in the three post form partials. These are assigned when a user selects an image, so they appear to be intentional upload previews rather than broken shipped images.
