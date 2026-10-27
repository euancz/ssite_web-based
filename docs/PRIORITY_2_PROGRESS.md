# Priority 2: dashboards, filtering, previews, and review tracking

Implemented October 27, 2026 after the owner's instruction to continue. This batch uses existing database fields. No application migrations or application records were changed.

## Completed

- **Actionable dashboards.** `ContentDashboard` calculates total, pending, published, rejected, and archived counts with one aggregate query per permitted module. Each count opens the matching workspace list. Non-President officers see only their own records in their currently assigned modules. The President sees the five oldest pending submissions across all modules. Archived and review states remain separate; the interface explains that some counts overlap.
- **Review queue.** The President can search by title or author, filter by module, submission date range, and active/archived state, and sort by oldest submission, newest submission, or latest decision. Status-tab counts reflect the same filters. Pagination retains validated filters and uses a stable type/ID tie-breaker. Pending submissions default to oldest first. Fixed the pending rejection modal's reference to a nonexistent `type` field.
- **Reviewer details.** The queue and private workspace previews show the latest saved reviewer and decision time, plus rejection feedback. Missing legacy metadata is explicitly marked as unrecorded. Public details do not receive the private summary. The officer dashboard now dates rejection feedback by the actual decision date rather than the original submission date.
- **Management search.** All five workspace libraries expose search and a clear-filter action. Article, Activity, and Achievement search matches title and body within the authorized tab/owner scope. Existing Document and Liquidation search behavior is retained. The document workspace now exposes its existing semester filter, and sorting/pagination preserve it alongside search and category.
- **Live content preview.** All five forms offer a collapsible preview of unsaved title, text, and relevant metadata, plus a word count reflecting the existing 300-word server limit. Text is assigned through `textContent`; entered markup remains text. Existing image previews remain available. PDFs continue to use the existing private canvas viewer after saving; no download, print, or direct-storage links were added. The form remains usable without JavaScript.
- **Responsive corrections.** Contained hidden table labels inside the scroll region to prevent page overflow on narrow screens. Constrained file inputs inside form grid columns. Review controls wrap into three, two, or one column, and decision controls meet the 44px minimum height. Removed an older display-font override that masked the shared Pixelify Sans token.

## How the pieces connect

`ContentModules` contains fixed model and label metadata shared by the dashboard service and review controller. Module/table/column names never come from unvalidated request input.

`Officer/DashboardController` sends the current authenticated user to `ContentDashboard`, which scopes queries before counting or selecting records. Blade renders those results through `workspace/content-summary`; each number links to an existing authorized workspace endpoint.

The review filter form sends a normal GET request to the existing President-only route. `Adviser/ReviewController` validates filters, combines the five content tables with a union, paginates the result, and fetches author/reviewer names in a single query. Existing CSRF-protected approve/reject forms still use the policies and transactional review service from Priority 1. This screen displays current decisions; it does not claim to be an immutable review history.

`ContentListing` applies ownership and publication boundaries before new workspace text searches. Module controllers retain their existing filters. The shared workspace sort component carries the selected search/filter values through sorting and normal or enhanced navigation.

`content-forms.js` reads local form controls to update the reusable `workspace/content-preview` component. It sends no preview request, persists no browser draft, and does not replace server validation or authorization. Shared interaction styles live in `public/css/ui-foundation.css`.

## Verification

- Full PHPUnit suite: **48 tests, 898 assertions, all passing**. The nine new tests cover dashboard scope/counts, oldest-first prioritization, all-module review actions, reviewer details/XSS escaping, combined filters and inclusive date boundaries, stable pagination, invalid inputs/forbidden roles, owner-scoped searches, document semester/sort preservation, and private summaries/form markup.
- Production Vite build: passed, no dependencies added. JavaScript syntax and PHP formatting checks passed.
- MySQL accepted the dashboard aggregates and filtered review union queries in an explicit **read-only transaction**, followed by rollback. No records or migration ledger entries were written.
- Headless Chrome rendered isolated SQLite fixture pages for the President dashboard, review queue, article form, and article workspace at **1440, 768, 360, and 320 pixels**. Final checks found no horizontal page overflow and no JavaScript exceptions. Wide tables retain their own scroll regions.
- Browser checks confirmed safe literal rendering of HTML-like preview input, the 301-word warning, and the non-JavaScript form fallback. Phone checks used reduced-motion preference. Screenshots and the local check report are development-only artifacts under `.tmp/priority2/`.
- Blade compilation, subsequent cache clear, and whitespace checks passed. The design detector reported no findings on the reviewed additions. Routes, authentication handlers, PDF delivery, notification JSON contracts, and role permissions were not changed.

Browser evidence uses rendered fixture pages, not a live Microsoft-authenticated browser session. Actual Microsoft sign-in, real uploads against MySQL, and full keyboard/screen-reader testing remain deployment checks. Existing feature tests exercise mocked authentication and isolated persistence.

## Remaining work

Private text/metadata drafts are implemented following approval and application of the targeted migration. See [Private drafts progress](DRAFTS_PROGRESS.md). The current suite passes 85 tests with 1,289 assertions, including document academic years, featured articles, article formatting, and staged review history.

Durable draft attachments, scheduling, full content version history, broader activity logs, and additional analytics remain later work. Do not run pending migrations against the installed database without the reconciliation and explicit review described in `DATABASE_BASELINE_REVIEW.md`.

Document academic-year assignment, draft persistence, and scoped filtering are implemented behind a schema check. The user approved the scoped migration, which was applied and verified without changing existing document values; see [review and validation](DOCUMENT_ACADEMIC_YEAR_REVIEW.md).

President-only featured-article controls and safe Home/Discover prioritization are implemented and enabled after approval of the scoped migration. Schema, migration ledger, unchanged existing article values, and the MySQL showcase query were verified; see [featured articles review](FEATURED_ARTICLES_REVIEW.md). Activities and achievements retain their existing display behavior.

Article formatting is implemented and enabled as an optional Markdown source editor with safe server rendering, formatted previews, draft persistence, and readable excerpts. The user approved the scoped migration; schema, default, ledger, and unchanged existing article values were verified. See [article formatting review](ARTICLE_FORMATTING_REVIEW.md). Legacy articles remain plain text. Other modules still use plain text.

Append-only application review history is implemented for explicit decisions across all five modules. The new table is prepared but not applied; see [review history migration review](REVIEW_HISTORY_REVIEW.md). It is not a tamper-proof ledger or full content revision history.
