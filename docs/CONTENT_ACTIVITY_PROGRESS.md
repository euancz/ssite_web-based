# Content activity reporting

Implemented October 27, 2026 using existing content and review-event tables. No migration, dependency, or application-data write was required.

## Available reporting

The workspace sidebar now links to Content activity. Authors see existing posts they own within their current module capabilities. The President sees existing posts across all modules. Filters select the last 7 or 30 calendar days, including today, and an optional permitted content area.

Two daily charts share the same vertical scale: submissions by original creation date, and recorded review decisions by event time. Approval and rejection totals are separated in text and in an expandable daily-values table. The native GET form, charts, and table work without JavaScript. Dates use the application's configured timezone, displayed beside the period; today is explicitly marked incomplete. Future-dated records are excluded through the current timestamp.

Submissions include pending, rejected, approved, and archived existing posts; they exclude private drafts and deleted posts. Edits and resubmissions do not create another submission count. Reviews count events, not unique posts: one post may contribute several decisions. Earlier unrecorded reviews and automatic publication are not inferred. If the history table is absent, the report shows review tracking as unavailable rather than claiming zero activity.

The explanation makes the current-scope limitation explicit: ownership changes, lost module permissions, and deletions can alter previous totals. This is not an immutable historical census or an engagement report. No views, downloads, or visitor counts are invented. A link opens the corresponding review-history date range and module.

## Implementation

`ContentActivity` aggregates each permitted content table by stored creation date, applying current ownership and the time window before grouping. It reuses `ContentReviewHistory::visibleQuery` for review-event visibility. Dates with no records are filled with zeros; an empty chart receives a nonzero scale to avoid division errors. Fixed module metadata supplies model/table identifiers.

`ContentActivityController` validates period and module, requires current content capability, and renders the workspace page. The new authenticated, profile-complete GET route is `workspace.content-activity.index`. The existing workspace shell allowlist includes it explicitly. Charts are hidden from assistive technology in favor of readable totals and the complete semantic daily table.

## Verification

- **94 tests, 1,362 assertions passed.** Four new tests cover exact start/end boundaries, future timestamps, original versus updated dates, archived/rejected posts, current owner/module scoping, repeated review events, orphan/private events, missing history schema, empty series, President filtering, invalid parameters, and role changes.
- Blade compilation/cache clear, PHP formatting, and whitespace checks passed.
- Chrome checked sparse and populated reports at 1440px, 768px, and 320px with no horizontal page overflow or JavaScript exceptions. Screenshots used isolated SQLite fixtures.
- The read-only MySQL aggregation check passed on retry: `.tmp/content-activity/inspect.php` verified one officer scope and the President aggregates, with zero writes. The earlier connection refusal is resolved for this check. The application database was not modified.

Scheduling, full content revisions, broader activity logging, and durable draft attachments remain separate work.
