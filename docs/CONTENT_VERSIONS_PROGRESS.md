# Content version history

## Scope and storage

All five content modules record text and metadata snapshots on creation and changed saves through `PostFiles`. Existing posts receive a baseline snapshot on their first changed save. Unchanged content, review decisions, archive actions, counters, featured selections, and attachment-only replacements do not create versions. Historical file bytes, paths, and workflow state are excluded. Existing approval rules continue to apply to edits.

History is read-only and paginated, linked from the workspace submission status. Current update authorization is checked on every request: owner with current module capability, or President. Deleted posts cannot be browsed. Historical text, including Markdown source, is escaped rather than rendered as HTML. No restore or republish endpoint is introduced.

`ContentVersions` uses an explicit per-module field allowlist. `PostFiles` records versions within its existing locked transaction, so a failed version insert rolls back the content save, and a failed draft consumption rolls back its versions. A first-edit baseline has no attributed editor: its timestamp is capture time, not an inferred historical edit date. Writes outside this application service are not tracked; this is not a database-level tamper-proof ledger. Snapshots remain stored after source deletion but are not exposed by the route.

## Additive migration

`2026_10_27_000006_create_content_versions_table.php` creates only `content_versions`: eight columns (`id`, `post_type`, `post_id`, nullable `editor_user_id`, nullable `editor_name`, `kind`, JSON `snapshot`, `created_at`) and an index on post type, post ID, and version ID. It does not modify existing tables or backfill content. No cascading foreign keys are added. Its destructive down migration must not be run against application data without separate approval.

The user authorized safe additive migrations on October 27, 2026 without further confirmation. Only this exact migration may be applied; older pending migrations remain out of scope. A read-only preflight confirmed database `ssitecms`, the absence of this table, and captured counts/hashes for all 12 existing non-ledger tables without exposing their contents. Verification and application results are recorded below when complete.

## Applied and verified

- Applied only `2026_10_27_000006_create_content_versions_table` after inspecting its generated SQL. Verified eight columns, the three-column index, one migration ledger entry, and zero initial snapshots.
- All 12 existing non-ledger tables have unchanged row counts and checksums. No historical records were backfilled or changed. Read-only checks are in `.tmp/content-versions/inspect.php`; no sensitive row contents are printed or persisted by the check.
- Full suite: **98 tests, 1,422 assertions passed**. New coverage includes all five modules, first-edit baselines, repeated versions, workflow-only exclusions, escaped snapshots, current ownership/capability access, President access, deleted posts, pagination, missing-schema compatibility, and rollback on version/callback failures.
- Chrome checked empty and populated history pages at 1440px, 768px, and 320px with expanded snapshots and long text. No horizontal overflow or JavaScript exceptions. Fixtures use isolated SQLite data; they do not create application content.
- PHP formatting and Blade compilation passed. The new schema was verified on MySQL; real content edits were not performed against the application database for this check.

This completes read-only text/metadata version history. Restoring versions, retaining old attachments, and capturing external database writes remain outside this batch.

## Comparison follow-up

Each expanded version now offers “Compare with previous version.” A scoped GET query selects that snapshot and the immediately preceding snapshot of the same post, even when other posts have interleaved version IDs. The comparison shows only changed fields, with Before/After source values side by side on larger screens and stacked on phones. Missing, null, and empty fields remain distinguishable. The first version explains that there is no predecessor. Closing the comparison preserves the current history page.

Both version lookups run after current update authorization and constrain post type and post ID. Invalid comparison IDs are validated; missing or foreign-post versions return 404. This introduces no write endpoint, migration, dependency, or JavaScript requirement. Source text is escaped, not interpreted as HTML or Markdown. Version IDs are global storage identifiers, not per-post sequential revision numbers. Comparison is field-level rather than a word-by-word diff, and compares recorded snapshots rather than the current live post.

Verification: six version-history tests pass with 79 assertions (two new comparison tests). They cover interleaved post versions, cross-post IDs, forbidden editors, invalid/missing IDs, first-version behavior, escaped content, and missing/null/empty/zero values. Chrome checked empty and populated comparison fixtures at 1440px, 768px, and 320px, with no overflow or JavaScript exceptions. PHP formatting and Blade compilation pass. The previous full suite remains 98 tests / 1,422 assertions; only the relevant version suite was rerun for this follow-up. Browser fixtures are isolated SQLite renders in `.tmp/version-comparison/`; no application database writes were performed.
