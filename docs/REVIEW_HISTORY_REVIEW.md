# Content review history: implementation and migration review

Prepared October 27, 2026. Following review and the user's instruction to continue, the scoped migration was applied successfully. Review history is enabled for future explicit decisions.

## Behavior and scope

Future explicit approval and rejection decisions across Articles, Activities, Achievements, Documents, and Liquidations append an event containing the module, record ID, reviewer ID/name snapshot, title snapshot, decision, rejection reason, and time. The event is inserted inside the same locked transaction that saves the decision. Failed event insertion rolls back the decision and prevents notifications. Stale or repeated decisions do not produce events.

Workspace previews show a native expandable Review history section with the latest 20 events, newest first. All events remain stored; the interface is deliberately bounded. History is available only to the President or the current owner with module edit permission, including when a published article can otherwise be viewed by other officers. Public pages never display the history. Names, titles, and reasons are escaped in Blade.

Existing reviewer/status fields remain the current-state summary. Earlier decisions are not reconstructed. Automatic publication on creation, edits, archive/restore, role changes, and featured selections are not approval/rejection events and are outside this batch. Title snapshots are not full content revisions.

The application exposes no update/delete endpoints or mutable event model. This is append-only application history, not a tamper-proof database ledger; privileged database access can still alter rows. Events intentionally survive deletion of posts or reviewer accounts. Retained snapshot names/reasons are not exposed through a public or orphan-history endpoint. Any later retention/deletion policy requires separate review.

## Approved database change

Create only `content_review_events`:

| Column | Definition |
| --- | --- |
| id | Unsigned BIGINT primary key, auto increment |
| post_type | VARCHAR(32), fixed application module type |
| post_id | Unsigned INT, matching existing content keys |
| reviewer_user_id | Unsigned INT, matching users.user_id |
| reviewer_name | VARCHAR(255), name at decision time |
| post_title | VARCHAR(200), title at decision time |
| decision | VARCHAR(20), approved or rejected from trusted service |
| reason | Nullable TEXT |
| created_at | TIMESTAMP, decision time |

Index `content_review_events_post_index(post_type, post_id, id)` supports scoped chronological reads. Polymorphic post references cannot use a single post-table foreign key. Reviewer references intentionally have no cascading foreign key so deletion cannot erase historical attribution. No existing table, value, file, or permission role is modified. There is no backfill.

Migration: `database/migrations/2026_10_27_000005_create_content_review_events_table.php`.

Pre-migration read-only MySQL inspection confirmed the table and migration-ledger entry were absent. Laravel pretend mode generated the CREATE TABLE and named index for review. The table uses configured utf8mb4 encoding.

After approval, only this command was executed:

```powershell
php artisan migrate --path=database/migrations/2026_10_27_000005_create_content_review_events_table.php --force
```

Verify the new columns, index, ledger entry, and empty initial event count. Capture and compare existing content-table counts/checksums before and after execution. Do not run unrelated legacy pending migrations. MySQL DDL is not generally transactional; inspect before retrying an interrupted migration. The down migration deletes review history and requires separate explicit approval.

Before migration the history UI is hidden and the existing review workflow continues without event writes. Once the table exists, explicit decisions require successful event insertion. This avoids silently losing an event after activation.

## Verification

- **85 tests, 1,289 assertions passed.** Seven new tests cover all five modules, prior-schema compatibility, preserved snapshots through rename/resubmission/archive, duplicate/stale/unauthorized requests, escaped reason display, editor-only access and capability changes, transaction rollback, bounded deterministic display, and retention after source deletion.
- Blade compilation/cache clear, PHP formatting, and whitespace checks passed. Cache operations ran before the suite to avoid Windows compiled-view file contention.
- Chrome checked empty and populated expanded histories at 1440px, 768px, and 320px: no horizontal overflow or JavaScript exceptions. The disclosure uses native HTML and does not depend on JavaScript.
- Browser pages and mutations use isolated SQLite fixtures, not application data or a live Microsoft-authenticated session. Read-only application schema inspection and SQL generation were the only database operations against MySQL in this batch.

Scheduling, full content revisions, broader activity logging, durable draft attachments, and additional analytics remain separate work.

## Application verification after approval

Artisan completed only `2026_10_27_000005_create_content_review_events_table`. Read-only checks confirmed all nine columns, the named composite index, ledger entry, zero initial events, and enabled feature availability. Counts and SHA-256 checksums of all rows in all five existing content tables matched before and after execution (Articles: 1, Activities: 0, Achievements: 0, Documents: 1, Liquidations: 1). No earlier decisions were invented and no unrelated migrations ran.
