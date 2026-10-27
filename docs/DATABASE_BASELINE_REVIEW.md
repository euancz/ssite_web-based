# Installed database baseline â€” review before migration execution

Source: read-only inspection of the configured MySQL connection, database `ssitecms`, on October 26, 2026. No rows, schema, or migration ledger entries were changed.

## Confirmed structure

| Tables | Keys / existing structures |
| --- | --- |
| `users` | INT UNSIGNED `user_id`; unique email and Microsoft ID; role, officer_position, profile completion and student fields |
| `articles`, `activities`, `achievements`, `documents`, `liquidations` | INT UNSIGNED module-specific primary keys; nullable author/reviewer IDs; approval_status, content_status, rejection_reason, reviewed_at, timestamps |
| `documents` | category, private file metadata, nullable semester (already present) |
| `liquidations` | report_date, DECIMAL(12,2) amount, private file metadata |
| `officer_terms` | `term_id`, academic_year, name/position/photo snapshot, nullable user_id; unique user_id/academic_year |
| `notifications` | UUID id, polymorphic recipient, text data, read_at, timestamps; recipient and unread indexes |
| `post_likes`, `post_views` | module type + post ID + user ID; unique user/post keys and module/post indexes |
| `migrations` | Only `2026_10_26_000001_add_semester_to_documents_table` was recorded during inspection |

All five content tables already have `(approval_status, content_status)` indexes and author/reviewer indexes. Activity, achievement, and liquidation date indexes exist; document category is indexed. None of the content tables had `like_count` or `view_count` during inspection. `role_changes` was absent. No draft, revision, scheduling, or tag storage was found.

## Why ordinary migration execution is not yet appropriate

The installed tables predate or were installed outside the checked-in migrations. Several checked-in create-table migrations would target tables that already exist. The ledger is not proof that these structures are absent, and blindly inserting migration names would conceal differences in columns, defaults, indexes, and foreign keys.

The semester migration is now tracked in source and already recorded in this database. Do not add or run a second semester migration.

## Proposed reconciliation process (not executed)

1. Back up the database and private/public file storage to an approved destination before deployment changes.
2. Capture complete schema-only DDL and compare each pending migration against the installed table, key types, constraints, defaults, indexes, and nullability.
3. Prepare a reproducible fresh-install baseline for the externally created core tables, plus a separate, explicitly reviewed adoption procedure for existing installations. Preserve `users.user_id` and module-specific keys.
4. Review every migration/adoption operation before execution. Never automatically mark mismatched migrations as applied.
5. Rehearse on an isolated MySQL copy, verify record counts and representative relationships, then obtain approval for the exact application migration operations.

Priority 1 needs no new database fields. Proposed future academic-year, draft, rich-content, scheduling, revision/event, and role-history migrations remain subject to review. Additional indexes should be justified by query plans. Existing engagement rows must be reconciled before displaying aggregate statistics; missing historical guest activity must not be invented.

## Approved additive change: October 27, 2026

The owner approved and the agent applied only `2026_10_27_000001_create_content_drafts_table`. Artisan reported successful completion. The subsequent October 27 read-only schema recheck succeeded: ledger, columns, index, and owner foreign key are present. This does not adopt or reconcile the older pending migrations. Existing content tables remain unchanged. See [draft storage review](DRAFT_STORAGE_REVIEW.md) and [implementation progress](DRAFTS_PROGRESS.md).

The user approved the scoped document academic-year migration. It was applied and verified: column, index, and ledger entry are present; document count and the hash of all pre-existing values are unchanged. Existing years remain unassigned. Older pending migrations remain unapplied. See [the scoped migration review](DOCUMENT_ACADEMIC_YEAR_REVIEW.md).

The user approved the scoped article `featured_at` migration. It was applied and verified: nullable timestamp, named index, and ledger entry are present. Article count and a hash of all existing values are unchanged; no article was automatically selected. The MySQL showcase query passed. See [featured articles migration review](FEATURED_ARTICLES_REVIEW.md). Older pending migrations remain unapplied.

The user approved the scoped article `content_format` migration. It was applied and verified: the nonnullable column defaults to `plain`, the ledger entry is present, and feature availability is true. Article count and the checksum of existing values are unchanged; the existing article remains plain text. See [article formatting migration review](ARTICLE_FORMATTING_REVIEW.md). Older pending migrations remain unapplied.

The user approved the scoped `content_review_events` migration. It was applied and verified: nine columns, named composite index, ledger entry, and zero initial events. Counts and checksums of all existing content tables are unchanged. It retains future explicit review-decision snapshots without backfilling older events. See [review history migration review](REVIEW_HISTORY_REVIEW.md). Older pending migrations remain unapplied.
