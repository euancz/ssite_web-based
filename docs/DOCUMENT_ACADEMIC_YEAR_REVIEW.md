# Document academic years — implementation and migration review

Prepared October 27, 2026. The user approved proceeding after review; the scoped application database migration completed successfully.

## Behavior

Documents can optionally carry an explicit consecutive academic year, such as `2026-2027`. Historical years from `1900-1901` are accepted. The create/edit forms offer suggestions without assigning a year automatically. Blank clears an assignment; an omitted field preserves the existing value on update. Draft save, resume, and PDF submission retain the year.

Public and workspace document lists filter by academic year, including “Not assigned,” alongside search, category, and semester. Choices derive only from records the viewer may see in the current tab. Sort and pagination retain the filter. Document metadata displays the assignment. Existing authorization, approval, notifications, and private PDF delivery remain in force.

Until the column exists, the feature is hidden and submitted academic-year input is excluded from writes. Existing document and draft workflows continue to work.

## Database change for review

Only `documents` gains nullable `VARCHAR(9) academic_year` and index `documents_academic_year_index`. No new table, relation, role, or permission is required. The migration ledger receives its normal entry when applied.

Migration: `database/migrations/2026_10_27_000002_add_academic_year_to_documents_table.php`.

Existing rows remain NULL (“Not assigned”). No timestamps or historical years are inferred, and existing content, files, ownership, and approval values are preserved. An index supports year filtering. MySQL may take a table lock during ALTER; duration depends on the installation size/version.

Read-only inspection confirmed both column and ledger entry are absent. Laravel's MySQL pretend mode generated the following without executing it:

```sql
alter table `documents` add `academic_year` varchar(9) null;
alter table `documents` add index `documents_academic_year_index`(`academic_year`);
```

Approved command executed successfully:

```powershell
php artisan migrate --path=database/migrations/2026_10_27_000002_add_academic_year_to_documents_table.php --force
```

Do not run the legacy pending migrations. Verify the new column, index, ledger entry, and preserved document count after execution. The down migration deletes stored year assignments and must not be run without separate explicit approval. MySQL DDL is not generally transactional; inspect the schema before retrying an interrupted migration.

## Validation

- Full isolated test suite: **66 tests, 1,089 assertions passed**.
- Six new feature tests cover record preservation, reversible schema on disposable SQLite, scoped filters, pagination/sort, validation, clearing versus omission, drafts/submission, and operation before migration.
- Blade compilation and cache clear passed. PHP formatting applied.
- Browser verification uses isolated fixture pages, not application records or a live Microsoft-authenticated session.

The existing content-drafts migration was rechecked successfully in MySQL: ledger, columns, index, and owner foreign key are present. The academic-year schema change was staged for review, then applied after the user approved continuing.

Chrome checks passed for public documents, workspace documents, and the create form at 1440px, 768px, and 320px: no horizontal overflow or JavaScript exceptions. The added filter exposed a tablet toolbar sizing issue, corrected by constraining the wrapping form to its container. Whitespace checks passed.

## Application verification after approval

Only the reviewed academic-year migration ran. Read-only checks confirmed the nullable varchar(9) column, named nonunique B-tree index, and migration-ledger entry. The feature availability check now returns true. Document count remained 1, and a SHA-256 comparison of all existing document columns in primary-key order matched before and after execution. The existing document has a NULL academic year; no historical assignment was inferred. No legacy pending migrations ran.
