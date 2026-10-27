# Article categories

## Scope

Articles now support one optional, bounded category value. Categories are
stored on `articles.category` as nullable `VARCHAR(100)` data and remain
separate from approval, ownership, featured selection, and content status.
Existing articles remain uncategorized.

## Authoring and filtering

- Article create, edit, and private draft forms accept an optional category.
- The field is ignored safely until the additive migration is available.
- Public and authorized workspace article lists expose a category filter.
- Category options are derived from the already authorized article query, so
  private, archived, or another owner's categories are not disclosed.
- Search and pagination preserve the selected category where the existing
  listing behavior supports them.
- Public and workspace cards display the escaped category when present.

## Authorization and persistence

Existing article policies, ownership checks, approval resets, notifications,
featured selection rules, and private/public boundaries are unchanged.
Category input uses the existing article request and draft validation paths.
Article version snapshots include the category value, while workflow-only
changes do not create a version.

## Migration

`database/migrations/2026_10_27_000007_add_category_to_articles_table.php`
adds only the nullable `category` column to `articles`. It does not rewrite
existing rows, add relationships, or remove any existing data. This migration
was applied as batch 8 after approval. Other environments should apply this
exact migration independently if it is still pending; the application remains
compatible with the pre-migration schema.

## Verification

`tests/Feature/ArticleCategoryTest.php` covers:

- authorized public filtering and category-option scoping;
- workspace search and pagination preservation;
- draft round-tripping, escaped display, validation, and review reset;
- authorization against another user's edits; and
- form/write compatibility before the migration.

The focused category suite and the full PHPUnit suite pass. Post-migration
verification confirmed that `articles.category` exists and the existing
article row count is unchanged at 1. This batch does not add tags, category
administration, or category filters to other modules.
