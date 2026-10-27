# Featured articles: implementation and migration review

Prepared October 27, 2026. Following review and the user's instruction to continue, the scoped application database migration was applied successfully. Featured-article controls are enabled.

## Editorial behavior

The President can select or remove a featured article from its workspace detail page. Only approved, active articles can be newly selected. The control is absent on public pages and for other roles. The dedicated PATCH route repeats policy authorization inside a transaction and locks the current article before validating its state. Stale selection forms return an error instead of overwriting another selection change.

Home uses the most recently selected public article. Discover uses selected public articles first, then recent public articles to fill its existing three article slots. Equal selection dates use creation time, then article ID as deterministic tie-breakers. Ordinary article list sorting remains unchanged. Selecting an already selected article does not advance its priority; remove and select it again to change priority.

Selections remain stored while an article is pending, rejected, or archived, but those articles never enter the public showcase query. If later approved/restored, they become eligible again. The President can remove a stored selection while the article is hidden. The control explains this behavior.

This batch covers articles only. Activities and achievements retain their current display behavior. No notification is sent and content, ownership, approval, review, creation, and update timestamps are untouched by editorial selection. Regular article writes and private drafts cannot assign the selection field.

## Approved additive migration

Affected table: `articles` only. Add nullable `featured_at` timestamp and nonunique `articles_featured_at_index`. The timestamp records selection priority; it is not a publication date or immutable audit history. No new relationship or permission role is needed. The President-only policy follows the existing editorial authority.

Migration: `database/migrations/2026_10_27_000003_add_featured_at_to_articles_table.php`.

Existing articles receive NULL, preserving recent-article behavior until the President selects content. All existing fields and files remain untouched. Before migration, the controls are hidden, mutation returns 404 after authorization, and showcase queries fall back to recent public articles.

Pre-migration read-only MySQL inspection confirmed the column and migration-ledger entry were absent. The pre-migration showcase query succeeded. Laravel pretend mode generated these statements for review:

```sql
alter table `articles` add `featured_at` timestamp null;
alter table `articles` add index `articles_featured_at_index`(`featured_at`);
```

After review approval, only this command was executed:

```powershell
php artisan migrate --path=database/migrations/2026_10_27_000003_add_featured_at_to_articles_table.php --force
```

Capture article count and a checksum of existing values before execution; verify the column, index, ledger, unchanged existing values, and NULL initial selections afterward. ALTER can lock the table; duration depends on database size/version. MySQL DDL is not generally transactional, so inspect before retrying any interrupted migration. Do not execute unrelated legacy pending migrations. The down migration deletes editorial selections and requires separate approval.

## Validation

Six isolated feature tests cover migration preservation, pre-migration operation, President-only access, public-page control exclusion, unchanged content/review/timestamps, no notifications, stale submissions, validation, removal of hidden selections, public showcase ordering and fallback, unchanged ordinary sorting, and forged article/draft fields. Tests use disposable SQLite; no application content is seeded for verification.

Full suite: **72 tests, 1,148 assertions passed**. PHP formatting, Blade compilation/cache clear, and whitespace checks passed. Chrome checked unselected, selected, and pending-selected workspace pages at 1440px, 768px, and 320px with no page overflow or JavaScript exceptions.

The browser checks use rendered SQLite fixtures and local assets rather than a live Microsoft-authenticated application session. No dependencies, frontend framework, or JavaScript behavior were added. The selection form uses ordinary HTML POST with CSRF and Laravel PATCH method override.

## Application verification after approval

Artisan reported successful completion of only `2026_10_27_000003_add_featured_at_to_articles_table`. Read-only checks confirmed the nullable timestamp, named nonunique B-tree index, migration-ledger entry, and enabled availability check. Article count remained 1. A SHA-256 comparison of all pre-existing article values in primary-key order matched before and after execution. The existing article's selection is NULL; no article was automatically featured. The showcase query also succeeded against the migrated MySQL schema. No unrelated pending migrations ran.
