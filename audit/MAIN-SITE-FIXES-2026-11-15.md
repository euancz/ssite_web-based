# Main site fixes — 15 November 2026

All four findings from [the main site review](MAIN-SITE-FUNCTIONALITY-2026-11-15.md) are addressed in the local application.

## Resulting behavior

- Global search, suggestions, Discover, and content-list searches match capitalization differences on PostgreSQL. Shared search handling binds values and treats `%`, `_`, and `!` as literal search text. Relevance ordering uses the same matching rules.
- Discover has one paginated feed containing all matching approved, active articles, activities, and achievements. It shows nine results per page, including the highlighted first result exactly once, and preserves the keyword, type, date, and sort filters in pagination links. Stale page numbers resolve to the last available page.
- Discover applies newest, oldest, and title ordering across the combined content types. Featured-first ordering places explicitly featured articles ahead of the remaining results. Sorting includes stable tie breakers. Database pagination runs before hydrating the current page's models and authors.
- Browsing or filtering any content list preserves unread publication notifications. Opening an authorized public detail page marks only the current account's matching publication notifications read, scoped by both post type and ID. Other posts, other accounts, review notifications, and inaccessible posts are unaffected. The individual notification action and explicit “Mark all as read” remain available.

## Verification

- **34 relevant PHP tests passed, 477 assertions.** Includes regression coverage for all-result pagination without duplicate highlighting, filter persistence, hidden-content exclusion, combined ordering, literal wildcard search, PostgreSQL search SQL, and notification ownership and read boundaries. Existing listing, gallery, featured-content, pagination, and Supabase setup tests passed. Evidence: `../../.tmp/main-site-fixed-tests.log` and `main-site-fixed-tests.xml`; new regression coverage is in `tests/Feature/MainSiteFunctionalityTest.php`.
- **Read-only checks against the configured PostgreSQL database passed.** Executed case-insensitive literal search and rejected wildcard false matches using synthetic text. Executed native Discover union/count/hydration queries in featured, recent, oldest, and title modes, plus the global search query. No live records were changed. Check script: `../../.tmp/main-site-fixed-postgres.php`.
- **Headless Chrome checks passed.** Discover pages 1 and 2 retain filters and show results 1–9 and 10–13 at 1440, 390, and 320 pixels, with no horizontal overflow. The highlight is not duplicated in the cards. Mobile suggestions, account and notification menus, AJAX article pagination, and sample PDF opening, zooming, and closing worked. The browser recorded no uncaught JavaScript exceptions or HTTP errors. Evidence: `../../.tmp/main-site-fixed-browser.json`; screenshots: `../../.tmp/discover-fixed-1440.png`, `discover-fixed-390.png`, and `discover-fixed-320.png`.

Browser checks use isolated sample data and local sample PDFs. Real Microsoft authentication and the deployed host were not tested. No database migration or frontend asset rebuild is required for these PHP and Blade changes. Existing unrelated working-tree changes were preserved.
