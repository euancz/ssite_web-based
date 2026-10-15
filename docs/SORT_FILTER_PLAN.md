# Sort and Filter Plan

## Step 1 audit

### Current list behavior

All five index controllers first validate the requested tab against the viewer's role-dependent `allowedTabs`, then build the corresponding visibility scope. Each list currently uses `latest('created_at')`, paginates ten rows, and calls `withQueryString()`.

| Module | Current default | Real list fields relevant to sorting/filtering |
| --- | --- | --- |
| Articles | Newest `created_at` first | `title`, `content`, `created_at`, `image`; no category/date/amount field |
| Activities | Newest `created_at` first (not event-date order) | `title`, `activity_date` (nullable date), `location`, `description`, `created_at` |
| Achievements | Newest `created_at` first (not achievement-date order) | `title`, `category` (nullable string), `awardee`, `achievement_date` (nullable date), `description`, `created_at` |
| Documents | Newest `created_at` first | `title`, `category` (nullable string), `description`, `file_size`, `created_at` |
| Liquidation | Newest `created_at` first | `title`, `report_date` (nullable date), `amount` (decimal), `description`, `file_size`, `created_at` |

Activities and Achievements index views render their feature strips; Document cards render category and file size; Liquidation cards render report date, amount, and file size. There is no Article category or Liquidation visibility filter field that justifies adding an extra filter. File size is present only for PDF metadata and is not proposed as a sort.

`DocumentController` currently gets categories globally from `Document::publiclyVisible()` regardless of the active tab. This must be replaced with a distinct-category query cloned from the current viewer/tab-scoped base query so private categories cannot leak. Achievements has a real category column but no fixed category list/config; its options will be distinct non-null categories from the viewer/tab-scoped query. A selected category is applied only when it is in that list.

### Search references and dependencies

- All five index controllers validate and apply a per-tab `search` query parameter, then pass `$search` to their index views. Articles and Liquidation search title plus author; Activities search title, description, location, and author; Achievements searches title, description, awardee, category, and author; Documents searches title.
- Each of the five index views passes `:search` and a module-specific label/id to `x-posts.feed-frame`.
- `resources/views/components/posts/feed-frame.blade.php` passes search state to `x-ui.workflow-tabs` and renders `x-posts.search-row`.
- `resources/views/components/posts/search-row.blade.php` owns the per-tab text input, label, submit button, and optional Documents category select.
- `resources/views/components/ui/workflow-tabs.blade.php` accepts search and includes it in tab links.
- The shared markup/styles are in `public/css/post-feed.css`; search-specific selectors are `.post-feed-search`, `.post-feed-search-input`, `.post-feed-search-button`, and `.post-feed-category`. These selectors must be checked against the whole stylesheet before removal; shared visually-hidden utility styling must remain if used elsewhere.
- Project-wide search found no per-tab search JavaScript or tests. `SiteSearch`, `SearchController`, the header search partial, `/search`, and search result links are separate and will remain untouched. The local parameter is `search`, while the header uses its own global query handling (`q`); stale local `search` or `q` values will be ignored when no longer read or carried through.
- The shared search-row component is only referenced by the shared feed frame. Delete it only after a fresh project-wide reference search confirms it remains unused after the frame change. Keep all global search files and notification behavior unchanged.

### Optional engagement schema check

Read-only schema inspection against the configured `ssitecms` database found `view_count` absent from Articles, Activities, Achievements, Documents, and Liquidation, and found no `post_likes` table. These features must therefore stay unavailable/inert until the owner creates the SQL schema. No tables or rows were changed.

### Route baseline

`php artisan route:list --json` was captured before changes in `audit/SORT_FILTER_ROUTES_BEFORE.json`. The app currently has 102 routes. Existing route names, URIs, methods, actions, and middleware are the baseline; the expected eventual diff is exactly one additional route, `posts.like`, after the plan is approved. No existing URL/name/middleware changes are planned.

### Issues found

- Documents category choices currently come from the public scope even on private workflow tabs; this is an existing information-scope mismatch that the category implementation must avoid repeating.
- The available database lacks all optional engagement schema, so live view/like behavior cannot be claimed until the user creates the columns/table.
- Current default ordering is `created_at` on all modules, including Activities; changing the default to event/report dates would alter existing behavior and is not proposed.

## Step 2 proposed behavior

### Sort and filter whitelist

Every sort is selected by a short request key and resolved only through a controller-owned map of fixed columns/directions. No request value reaches `orderBy` or raw SQL. Apply the selected filter to the tab-scoped query, then apply its validated sort map, then append the module primary key ascending as a stable pagination tie-breaker. The primary key direction remains ascending for ties.

| Module | Default key and order | Published sort keys | Published filter | Other tabs |
| --- | --- | --- | --- | --- |
| Articles | `recent`: `created_at DESC` | `oldest`: `created_at ASC`; `title`: `title ASC`; conditional `viewed`: `view_count DESC`; conditional `liked`: likes count DESC | None: no real category/date/amount column | `recent`, `oldest`, `title` only |
| Activities | `recent`: `created_at DESC` | `oldest`: `created_at ASC`; `title`: `title ASC`; `event_soonest`: `activity_date ASC`; `event_latest`: `activity_date DESC`; conditional `viewed`, `liked` | `timeframe`: `all`, `upcoming` (`activity_date >= today`), `past` (`activity_date < today`); null dates remain in All only | `recent`, `oldest`, `title`; timeframe remains available |
| Achievements | `recent`: `created_at DESC` | `oldest`: `created_at ASC`; `title`: `title ASC`; conditional `viewed`, `liked` | `category`, chosen from scoped distinct non-null values | `recent`, `oldest`, `title` |
| Documents | `recent`: `created_at DESC` | `oldest`: `created_at ASC`; `title`: `title ASC`; conditional `viewed`, `liked` | `category`, chosen from scoped distinct non-null values | `recent`, `oldest`, `title` |
| Liquidation | `recent`: `created_at DESC` | `oldest`: `created_at ASC`; `title`: `title ASC`; `report_soonest`: `report_date ASC`; `report_latest`: `report_date DESC`; `amount_high`: `amount DESC`; conditional `viewed`, `liked` | None: the existing tab is login-only and no other useful real filter is justified | `recent`, `oldest`, `title`, `report_soonest`, `report_latest`, `amount_high` |

The `viewed` key is available only when `school.enable_view_counts` is true and that module's `view_count` column exists. The `liked` key is available only when `school.enable_likes` is true and `post_likes` exists. Both are offered only on Published; they are excluded from all other tabs. If the required schema/flag is missing, if the key is unknown, or if the key is disallowed for the active tab, the sort becomes that module's default. Empty counts naturally order as zero; the stable key tie-breaker handles equal values.

Filters are validated against the option list generated from the already authorized tab query. Invalid, empty, oversized, and out-of-list values are ignored and resolve to All. Category option generation is skipped unless the module has a category field and its filter control is in use.

### UI wireframe

Desktop: the existing tabs and one compact native-select GET form share one line; the form aligns at the right end. Phone: the form sits directly beneath the tabs without a separate heading/band. The row contains Sort and, where applicable, Filter selects with visually hidden labels, a submit button visible without JavaScript and hidden after the progressive-enhancement script starts, and a Reset link only while a non-default choice is active. The form includes `tab`, omits `page`, and contains only `sort`/filter state. Tab links submit no prior sort/filter state, resetting choices when tabs change. Pagination preserves valid sort/filter state with `withQueryString()`.

The selects will use existing SSITE tokens in `public/css/post-feed.css`, keep at least 44px control height, visible focus, contrast, and compact spacing. Empty results caused by an active sort/filter will show a short filtered-empty message and Reset link; ordinary empty states remain for unfiltered defaults. With JavaScript off, the visible Apply button submits the GET form.

### View-count and likes design

- Add `config/school.php` flags `enable_view_counts` and `enable_likes`, both false by default, `view_dedupe_hours` defaulting to 12, and a short bot-agent fragment list. False flags must avoid counting, count/like UI, engagement sort choices, and engagement queries; the like endpoint returns 404 while disabled or when the table is missing.
- The show handlers count only approved active posts, excluding authors and configured bot user-agent fragments. A session key per type/post deduplicates views for the configured window. Increment `view_count` without changing `updated_at`; do not count pending/rejected/archived content. Counts are integer casts and never mass assignable.
- Add one `PostLike` model for `post_likes` (`like_id` key, no mass assignment), plus a `likes()` has-many relationship on each post model constrained by a hard-coded post-type constant and that model's custom post key. Toggle is unique per signed-in `user_id` and post; archive or a race must not create duplicate likes. Permanent post deletion should remove its likes. The UI shows no liker identities; hide zero counts per the proposed default.
- Add one `PostLikeController` and the sole new named route `posts.like`, authenticated and profile-complete, with the per-module policy/visibility check before toggling. Guest form submission redirects to login with the intended post URL. Adviser and superadmin like permission and whether zero counts are hidden require the owner's decision below.
- No migration/seeder/schema write is planned. If later implementation needs a compatibility migration, it will be guarded and no-op only; SQL table/column creation remains the owner's task.

### Decisions recorded during implementation

1. Likes are available to signed-in users who pass the target post's `view` policy; advisers and superadmins follow the same rule.
2. Zero view/like counts are hidden from feed cards.
3. Articles and Liquidation have no additional filters; sort choices use their existing fields.
4. One `PostLikeController` and one `posts.like` route handle all five post types.

### Files in scope

Batch 1 (Articles): `app/Http/Controllers/ArticleController.php`, `resources/views/articles/index.blade.php`, shared feed frame/tab/search-row components as needed, `public/css/post-feed.css`, and `docs/SORT_FILTER_PROGRESS.md`.

Batch 2 (Activities and Achievements): `app/Http/Controllers/ActivityController.php`, `app/Http/Controllers/AchievementController.php`, their index views, shared controls/styles only as needed, and progress doc.

Batch 3 (Documents and Liquidation): `app/Http/Controllers/DocumentController.php`, `app/Http/Controllers/LiquidationController.php`, their index views, shared controls/styles only as needed, and progress doc.

Batch 4 (engagement, inert by default): `config/school.php`, five post models, new `app/Models/PostLike.php`, new `app/Http/Controllers/PostLikeController.php`, five show handlers/views or shared post card as appropriate, `routes/features.php`, any guarded no-op migration only if proved necessary, and progress doc.

Documentation after batches: update relevant sections in `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, and `docs/SORT_FILTER_PROGRESS.md`. Candidate deletion: `resources/views/components/posts/search-row.blade.php`, only after a project-wide search confirms no remaining consumers. Do not touch header search, `SearchController`, `SiteSearch`, notifications, routes other than the approved `posts.like` addition, or files outside the current approved batch.

### Risks and build order

1. Batch 1 removes local search while adding Articles sort support and captures reviewable route/view changes.
2. Batch 2 adds date sorting and Activity timeframe plus Achievements category options, scoped after visibility.
3. Batch 3 adds Document category scoping and Liquidation date/amount choices.
4. Batch 4 adds optional schema-backed counts/likes and keeps every feature disabled with the current database schema.
5. Update the system/where-is-what docs after feature batches, then provide the manual checklist and issues found.

Risks include null dates appearing at an engine-defined edge of date sorting; the implementation should specify null placement consistently (null dates last) while preserving the existing `recent` default. Category values may be absent/duplicated, so option building must exclude null/empty values and deduplicate. Aggregate likes must be scoped by post type/key and must not multiply base rows. Session view dedupe is per browser session and does not stop a user from creating fresh sessions. Visibility authorization must run before any engagement write or counts query. Existing routes must retain their current names, middleware, and URLs.

## Completion state

The plan was approved and implemented in four review batches. Documentation was updated in `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, and `docs/SORT_FILTER_PROGRESS.md`. Optional engagement remains disabled because the configured external database does not contain the required `view_count` columns or `post_likes` table. See the progress document for verification results and the remaining manual browser/schema checklist.
