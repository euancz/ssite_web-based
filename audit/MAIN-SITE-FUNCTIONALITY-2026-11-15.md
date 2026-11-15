# Main site functionality review — 15 November 2026

**Update:** All four findings below have been fixed. The original review is retained as the before-state; implementation and fresh verification are recorded in [MAIN-SITE-FIXES-2026-11-15.md](MAIN-SITE-FIXES-2026-11-15.md).

Scope: the signed-in student website. The workspace, landing page, and Flutter app are excluded. No application code or existing account/content records were changed. Audit helpers and evidence are under `../../.tmp/`.

## Findings

### 1. Search misses matches when capitalization differs

**Priority: medium.** The configured database is PostgreSQL, but the global search, suggestions, Discover, document title search, and post-list searches use `LIKE`. Matching is case-sensitive on this database. A user searching `workshop` can miss a title containing `Workshop`; the SQLite test database masks this difference.

Verified with a read-only query against the configured PostgreSQL connection, using literal sample text only: `'Workshop' LIKE '%workshop%'` returned false, while `'Workshop' LIKE '%Workshop%'` returned true. No user records were needed for this check.

Affected code includes `app/Support/SiteSearch.php` (`applyWords` and `applyTitleOrder`), `app/Http/Controllers/DiscoverController.php:38`, `app/Http/Controllers/DocumentController.php:84`, and `app/Support/ContentListing.php`.

Recommended correction: use database-appropriate case-insensitive matching consistently for results and relevance ordering; verify it with PostgreSQL integration coverage.

### 2. Discover stops at three posts per type without a continuation

**Priority: medium.** With eight approved articles, `/discover?type=articles&sort=recent` returns only three. The page has no pagination, load-more control, or link to the full articles list. Its highlighted story repeats the first result. The same limit applies to activities and achievements.

Students following the main Discover navigation cannot browse the remaining matching posts from that screen. They must change the search/filter or reach a separate listing through another path.

Reproduced with isolated fixtures. `app/Http/Controllers/DiscoverController.php:51` applies `limit(3)` to each module; `resources/views/pages/discover.blade.php:432` renders those collections without a continuation.

Recommended correction: add pagination or explicit links to full filtered listings and show the available result count.

### 3. Discover's sorting does not sort the combined feed

**Priority: medium.** On the all-content view, “Newest added” and “Title (A to Z)” sort each module independently. The template then renders articles before activities before achievements, and always chooses an article for the highlight if any article matches.

Reproduction: create an approved `Zulu old article` dated 30 days ago and an approved `Alpha new activity` dated today. Both `sort=recent` and `sort=title` still put the older Zulu article before the new Alpha activity.

Affected code: ordering in `app/Http/Controllers/DiscoverController.php:43` and highlight/group rendering in `resources/views/pages/discover.blade.php:394` and `:432`.

Recommended correction: merge matching items before applying the selected ordering and pagination, or explicitly present separate sections with clearly scoped sort labels.

### 4. Filtered listings clear notifications for posts the student has not seen

**Priority: medium.** Opening `/documents?q=no-such-document` marks all of the current student's unread document publication notifications as read even when the page contains zero documents. Opening the first page also clears notices for posts on later pages. The other post-list controllers use the same module-wide update.

Reproduced with an isolated unread document notification and an empty search result. The notification's `read_at` changed despite no matching document being displayed.

Affected code: `app/Http/Controllers/DocumentController.php:125`, `ArticleController.php:100`, `ActivityController.php`, `AchievementController.php`, and `LiquidationController.php:119`.

Recommended correction: mark individual notifications read when opened, or restrict automatic marking to the post IDs actually displayed. Keep the explicit “Mark all as read” action for clearing everything.

## Verification and limits

- Relevant existing server checks: **25 passed, 310 assertions**, covering listing filters, document gallery filters, featured articles, and pagination. Evidence: `../../.tmp/main-site-tests.log` and `main-site-tests.xml`.
- Three additional isolated reproduction checks passed, confirming findings 2–4 rather than claiming those behaviors are correct: **3 checks, 11 assertions**. Evidence: `../../.tmp/MainSiteAuditTest.php` and `main-site-reproductions.log`.
- Headless Chrome rendered 14 authenticated main-site routes at 1440, 390, and 320 pixels: **42 page/viewport checks**. No detected horizontal overflow, broken images, or uncaught JavaScript exceptions. The pages were Home, Discover, article list/detail, activity detail, achievement detail, document list/detail, transparency, liquidation detail, About, search, notifications, and profile edit. Evidence: `../../.tmp/main-site-browser-results.json`.
- Completed browser interactions: mobile search opened and returned ten suggestions; account and notification menus opened; Discover retained the selected filters and showed three matching cards; AJAX article pagination reached page 2 and announced results 6–10 of 13; the sample PDF rendered, zoomed to 125%, and closed successfully. No uncaught JavaScript exceptions or HTTP error responses were recorded in this interaction pass. Evidence: `../../.tmp/main-site-interactions.json`. Earlier fast interaction snapshots in the full rendering evidence were taken before requests settled; use this dedicated completed pass for interaction outcomes.
- Browser checks use an isolated SQLite database, a sample authenticated student, and sample PDFs. They do not exercise real Microsoft authentication or certify existing uploaded media or the deployed host. External Google Fonts requests were blocked for deterministic local rendering.
- An exploratory broader test selection also reached upload/workspace tests that could not create sandboxed fixture folders. Those failures are outside this review's scope and are not reported as application defects. This is not a full-suite pass claim.
- Profile editing was rendered; saving real profile data, sending notifications to real users, and modifying production content were not performed.

