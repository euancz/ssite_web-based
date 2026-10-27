# Review-history browsing

Implemented October 27, 2026 using the existing `content_review_events` table. No migration or application-data write was required.

## Available now

The workspace library has a Review history link. Authors can browse historical decisions for posts they currently own and have module edit permission to manage. The President can browse decisions for existing posts across all five modules. Each post's history also links to a view filtered to that exact module and post ID.

The page searches recorded titles and filters by content area, approval/rejection, and inclusive date range. It offers newest/oldest ordering, twenty events per page, retained filters across pagination, and an event-ID tie-breaker when times match. Snapshot titles link to the current workspace post. Reviewer names and titles remain historical snapshots rather than current names/content.

The ordinary GET form and pagination work without JavaScript. The responsive form reuses the existing workspace filter styles; the data table retains its keyboard-focusable scroll region. The page explicitly uses the workspace shell.

## Access boundaries

The controller requires authentication, a completed profile, a current content capability, and the installed history table. The database query scopes by current module permissions and existing post ownership before search, date filtering, counts, or pagination. Fixed model metadata supplies identifiers; request values are validated and bound.

Public posts belonging to another author do not expose that author's history. Events for deleted posts remain stored but are excluded from this page, including for the President. Losing a module capability removes its history from the author's results. Unsupported and malformed filters are rejected. Historical names, titles, and reasons are escaped. The page adds no mutation or export endpoint.

## Verification

- Full suite: **90 tests, 1,334 assertions passed**. Five new tests cover role access, absent schema, owner/module/deleted-post boundaries, capability changes, combined filters, pagination and stable ordering, invalid input, escaped snapshots, and workspace links.
- A subsequent workspace-shell regression assertion and targeted rerun passed: **5 tests, 46 assertions**. The browser found the new route initially omitted from the workspace-shell allowlist; that was corrected before completion.
- Chrome checked empty and populated pages at 1440px, 768px, and 320px. Final checks found no horizontal page overflow or JavaScript exceptions. Wide tables scroll within their own region.
- Read-only MySQL transactions successfully exercised the current officer's visibility query and the President branch without writing records.
- Blade compilation/cache clear, PHP formatting, and whitespace checks passed. No dependencies or new JavaScript were added; screenshots used isolated fixtures rather than a live Microsoft-authenticated session.
