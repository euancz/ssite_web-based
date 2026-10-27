# Draft storage: approved migration

Prepared October 27, 2026, subsequently approved and applied to `ssitecms`. Draft controls and endpoints are now implemented; see [Private drafts progress](DRAFTS_PROGRESS.md). The design and pre-execution verification below are retained as the migration review record.

## Proposed change

Create one independent `content_drafts` table. Drafts must not be stored as pending submissions: that would make unfinished work visible to reviewers and could trigger submission notifications. Keeping drafts separate also avoids changing the existing approval enums, public visibility queries, and required PDF columns.

| Column | Type | Purpose |
| --- | --- | --- |
| `draft_id` | UUID / CHAR(36), primary key | Stable draft identifier. UUIDs do not replace authorization. |
| `created_by_user_id` | INT UNSIGNED, required | Owner, referencing the installed `users.user_id` key. |
| `module` | VARCHAR(24), required | One of the five content modules, to be allowlisted by the draft request. |
| `payload` | JSON, required | Editable form values; not ownership, approval, reviewer, or storage-path fields. |
| `lock_version` | INT UNSIGNED, default 1 | Upcoming save logic can compare and increment the version to reject stale browser tabs. The field alone does not implement concurrency protection. |
| `created_at`, `updated_at` | Nullable timestamps | Standard Eloquent timestamps for saved draft listings. |

An `(created_by_user_id, module, updated_at)` index supports owner/module listings. InnoDB supports the owner foreign key. `ON DELETE RESTRICT` prevents deleting a user while their drafts exist; drafts are not silently cascaded away.

**Affected existing structures:** `users` is referenced but its columns and rows are not altered. The five content tables, notifications, officer terms, and engagement tables are untouched. Existing records need no backfill or transformation. The targeted migration will add its normal entry to the migration ledger when explicitly approved and executed.

## Migration for review

File: [`2026_10_27_000001_create_content_drafts_table.php`](../database/migrations/2026_10_27_000001_create_content_drafts_table.php).

It deliberately fails if a table with this name already exists. It does not silently adopt, truncate, or replace an unknown table. MySQL DDL is not treated as a rollbackable application transaction; if execution fails partway through, inspect the actual table and migration ledger before retrying. Do not automatically drop partial structures.

The operation is equivalent to:

```sql
CREATE TABLE content_drafts (
    draft_id CHAR(36) NOT NULL PRIMARY KEY,
    created_by_user_id INT UNSIGNED NOT NULL,
    module VARCHAR(24) NOT NULL,
    payload JSON NOT NULL,
    lock_version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE content_drafts
    ADD CONSTRAINT content_drafts_created_by_user_id_foreign
    FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE RESTRICT;

ALTER TABLE content_drafts
    ADD INDEX content_drafts_owner_module_updated_index
    (created_by_user_id, module, updated_at);
```

After approval, use **only this migration's path**:

```powershell
php artisan migrate --path=database/migrations/2026_10_27_000001_create_content_drafts_table.php
```

Do not run unscoped `migrate`, `migrate:fresh`, or `migrate:refresh`: older create-table migrations still conflict with the installed legacy schema. This targeted addition does not reconcile that older migration history. See [database baseline review](DATABASE_BASELINE_REVIEW.md).

The migration's `down()` removes only `content_drafts`, but would delete every saved draft in it. Application rollback is **not approved** by approval to create this table. Once drafts exist, prefer disabling the draft feature while retaining its data; dropping the table requires separate approval and an appropriate backup.

## Approved draft workflow

1. Private draft list and save/resume controls inside the workspace, limited to the current owner and their current module capability. Even the President cannot read another person's unsubmitted draft through the draft endpoints.
2. Validate an explicit allowlist of editable values. Allow incomplete text while still bounding field sizes. No approval status, reviewer, user ID, published flag, or private storage path is accepted from the payload.
3. Compare the submitted version with the locked database version on saves and submission; return a recoverable conflict rather than overwriting a newer edit.
4. Submit through the existing module validation, upload rules, policy, and review workflow. Only successful submission creates a normal content record and sends the existing notification. Failures retain the draft.
5. Keep uploads explicit: a text/metadata draft must clearly say that selected files are not saved. Durable draft attachments, if added, need private storage, authorization, retention, and cleanup rules before enabling them. No private PDF download/print/direct-path controls will be introduced.

This is storage for unfinished new submissions. Draft revisions of already published posts, scheduling, rich text, featured-post selection, and immutable version history are separate changes.

## Verification

- Read-only inspection confirmed `users.user_id` is the primary `INT UNSIGNED` key, `users` uses InnoDB, and `content_drafts` does not exist in `ssitecms`.
- Laravel's MySQL grammar generated the create-table, owner foreign key, and index statements in pretend mode; these statements were not executed against MySQL.
- Five isolated SQLite tests passed: preserving existing records, storing JSON/version defaults and the listing index, rejecting missing owners, preventing silent owner/draft cascade deletion, refusing duplicate-table adoption, and limiting rollback to the new table.
- The tests require SQLite `:memory:` before creating fixtures or running either migration direction. No application migration ledger or application data was changed.
- Full regression suite: **53 tests, 911 assertions, all passing**. PHP formatting, syntax, and whitespace checks passed.
- At review time, MySQL DDL execution was pending approval. The approved targeted migration has since completed successfully; post-implementation verification is recorded in `DRAFTS_PROGRESS.md`.
