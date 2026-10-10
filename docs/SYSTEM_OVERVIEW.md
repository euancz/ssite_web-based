# SSITE System Overview

SSITE is a Laravel Blade application for the Student Society in Information Technology Education at Mabalacat City College. Users sign in with Microsoft or the existing password login, then complete required student information before using protected pages. The app stores identity, role, and student information on one `users` table with `user_id` as its primary key. Articles, Activities, and Achievements use the same separate posting, review, and archiving workflow.

## Roles

| Role | Upload PDFs | Approve or reject | View or download PDFs | Other capabilities |
| --- | --- | --- | --- | --- |
| Student | No | No | Published PDFs after login and profile completion | View published Articles, Activities, Achievements, Documents, and Liquidation; edit allowed personal information. |
| Officer | Documents and Liquidation | No | Published PDFs and own non-public PDFs after login and profile completion | Post the other content types; edit and archive/restore own posts. New submissions start pending. |
| Adviser | Documents and Liquidation | Yes; pending items only | Published PDFs and non-public records they manage | Post the other content types; archive/restore any post, permanently delete, bulk archive, and manage non-adviser user roles. Adviser-created PDFs are auto-approved by the school setting. |

The current code enforces dashboard and adviser route roles, and adviser role changes are implemented. About page leadership is stored as annual officer snapshots managed by advisers. Article, Activity, and Achievement posting, ownership checks, approval, rejection, archive/restore, and approved-only queries use their existing SQL-managed tables.

## Request flow example

An adviser opening `/adviser/users` follows this path:

1. `routes/adviser.php` matches the named `adviser.users.index` route.
2. Laravel runs `auth`, `role:adviser`, and `profile.complete` middleware.
3. `App\Http\Controllers\Adviser\UserController::index` validates filters and queries users through the `User` model.
4. The controller renders `resources/views/adviser/users/index.blade.php`, which uses shared components from `resources/views/components/ui`.

Profile editing uses the same authenticated-user pattern: the profile controller obtains the user from the request session rather than accepting a user ID from the form.

An officer viewing their own pending Articles follows this path:

1. `routes/features.php` matches `articles.index` at `/articles` with the `tab=pending` query value.
2. The route applies `profile.complete`, which lets guests pass and redirects incomplete signed-in accounts; write routes separately require `auth`, `role:officer,adviser`, and `profile.complete`.
3. `ArticleController::index` validates the tab against the signed-in role, scopes the query, and searches/paginates only those rows.
4. `Article` maps `article_id`, relates authors through `users.user_id`, and `ArticlePolicy` governs private article viewing.
5. `resources/views/articles/index.blade.php` renders the role's tabs and results with the shared Blade components.

## Microsoft login and forced profile completion

1. The user starts Microsoft OAuth at `/auth/microsoft`; Socialite returns to the callback.
2. `MicrosoftAuthController` finds the local account by Microsoft ID, or links a matching email account if it is not already linked.
3. If no local account exists, the callback creates a student account with Microsoft-provided name/email and server-set authentication fields. A picture is not fetched; a Microsoft photo URL is retained only when an existing account already has one.
4. The user is logged in. If `profile_completed_at` is null, the callback sends them to `profile.complete`; otherwise, it redirects to an intended URL or the route selected for their role.
5. `EnsureProfileCompleted` redirects an incomplete account away from protected routes. The completion form and logout are exempt so the user can finish the form or leave.
6. `ProfileController` validates and saves the signed-in user's allowed profile fields, sets `profile_completed_at`, and redirects onward. Picture upload/removal uses separate authenticated actions; completing required fields does not require a picture.

`config/school.php` controls the school email domain, profile enforcement flag, allowed gender/year values, student-number format, and TODO institute/program lists.

## Officer history

`AcademicYear::current()` calculates the current A.Y. using `school.academic_year_start_month` (June by default), so the changeover happens from the calendar date without a scheduled task. Adviser-managed `officer_terms` rows are snapshots: name, position, and photo stay with that year even if a linked account changes or is deleted. Role changes synchronize only the current year's row. New officers are not copied forward automatically; an adviser can use the explicit copy action as a starting point. The public About page shows current-year rows above newest-first history rows.

## Post approval flow

Articles, Activities, Achievements, Documents, and Liquidation share this workflow and keep approval and content status separate:

1. An officer creates a post owned by that officer. The server sets `approval_status=pending` and `content_status=active`; advisers can post directly approved by the school setting.
2. The adviser reviews pending posts and either approves them or rejects them with a required reason.
3. Public/student-facing queries return only `approval_status=approved` AND `content_status=active` records.
4. Archiving changes only `content_status` to `archived`; restoring changes it to `active` and preserves approval state, so a pending or rejected post does not become published.
5. Officers can edit and archive/restore only their own posts; advisers can manage all posts, bulk archive, and permanently delete.

The Articles, Activities, Achievements, Documents, and Liquidation tables were created with SQL outside this repository's migrations. Adviser review is provided by each module's tabs; the legacy adviser dashboard review page still contains placeholder content for other post types.

## Site search

Header suggestions and the `/search` page use `app/Support/SiteSearch.php`; `SearchController` serves both the page and JSON suggestion endpoint. Both endpoints are throttled to 60 requests per minute and share the same normalization: control characters become spaces, repeated whitespace is collapsed, input is capped at 100 characters and five words, and queries shorter than two characters return no matches before feature queries run. Every word must match at least one allowed field using bound LIKE patterns with escaped wildcard characters. Results rank title prefixes first, title substrings second, and other-field matches third; newest date breaks ties. The search is LIKE-based and may need FULLTEXT indexes if the data grows large.

Search never queries users or profile fields. Articles, Activities, Achievements, Documents, and Liquidation reuse each model's `publiclyVisible()` scope, so only approved and active records appear. Search fields are feature-specific: Articles use title/body; Activities title/description/location; Achievements title/description/awardee/category; Documents title/description/category; Liquidation title/description; officer snapshots name/position/academic year. Guests can be restricted from Documents or Liquidation by `search_include_documents_for_guests` and `search_include_liquidation_for_guests`, which default to the matching list-public settings. Documents and Liquidation respect those visibility settings; file paths, PDFs, liquidation amounts, and user records are never returned from search. Results link to the feature's show page (officer matches link to About). The suggestion endpoint returns at most three matches per type and ten overall; the results page offers type filters and ten results per page.

To change the minimum query length, dropdown limits, relevance order, or searched columns, edit `app/Support/SiteSearch.php` and the header search UI in `resources/views/layouts/app.blade.php` as applicable.

### In-app notifications

| Event | Recipients | Notification |
| --- | --- | --- |
| Officer submits a pending post | All advisers | `submitted`; a new post needs review |
| Officer edit returns approved/rejected post to pending | All advisers | `submitted`; needs review again |
| Adviser approves a pending post | Author, then everyone except author and adviser if active | Author receives `approved`; others receive `published` |
| Adviser rejects a pending post | Author only, when the account exists | `rejected` with the reason |
| Adviser creates an auto-approved active post | Everyone except that adviser | `published` |
| Archive, restore, permanent delete | Nobody | Silent; restore never republishes |

Displayed messages are “A new post was submitted for review.”, “A post needs review again.”, “Your post was approved.”, “Your post was rejected.”, and “A new post was published.” Rejection notices also include the saved reason, limited to 255 characters in the stored payload. Notices carry the post type/id, title, actor name, and named show-page URL; they do not include PDF paths.

Notifications are synchronous database notifications. Pending items are announced only to advisers, and the author is excluded from the separate publication broadcast to prevent duplicate notices. Recipients are processed in batches of 100, and notification exceptions are logged without failing the post action. Users with incomplete profiles are included because this app has no inactive-account flag; the profile gate controls page access instead. The bell summary returns the latest 10 items and unread total; `/notifications` paginates by 15 and supports All/Unread. Only unread `published` notices contribute to navigation tab dots; approval, rejection, and submission notices do not. Visiting the matching index clears only that user's published notices for that type. Read, read-all, dismiss, and avatar routes are scoped to the signed-in user's records. A notice for an archived, deleted, or otherwise unavailable post redirects with a friendly message. Archive and restore are silent and restore never republishes.

Documents and Liquidation store PDFs on Laravel's private `local` disk under `storage/app/private/documents` and `storage/app/private/liquidation`. Authorized controller routes stream or download files after policy checks; the files are not exposed through `storage:link`. Documents may have a public listing (`school.documents_list_public`, default true), but PDF access requires login and a completed profile. Liquidation listing access follows `school.liquidation_list_public` (default false). Both use `school.max_pdf_size_kb` (default 10240).

## Folder map

| Location | Purpose | Important files |
| --- | --- | --- |
| `routes/` | URL definitions grouped by feature and access role | `web.php`, `auth.php`, `profile.php`, `officer.php`, `adviser.php`, `features.php` |
| `app/Http/Controllers/` | Login, profile, dashboard, adviser, About, and post request handling | `AboutController.php`, `ArticleController.php`, `ActivityController.php`, `AchievementController.php`, `DocumentController.php`, `LiquidationController.php`, `Auth/`, `Profile/`, `Officer/`, `Adviser/OfficerTermController.php` |
| `app/Http/Controllers/` | Public full-page and live suggestion search endpoints | `SearchController.php` |
| `app/Support/` | Shared site-wide search normalization, matching, result mapping, and highlighting | `SiteSearch.php` |
| `app/Http/Middleware/` | Authentication, role, and profile-completion gates | `EnsureProfileCompleted.php`, `RoleMiddleware.php` |
| `app/Http/Requests/` | Validation and authorization grouped by feature | `Activity/`, `Achievement/`, `Article/`, `Document/`, `Liquidation/`, `Profile/`, officer term requests |
| `app/Models/` | User, post, and officer term persistence | `User.php`, `Article.php`, `Activity.php`, `Achievement.php`, `Document.php`, `Liquidation.php`, `OfficerTerm.php` |
| `app/Policies/` | Per-record authorization for post visibility and actions | `ArticlePolicy.php`, `ActivityPolicy.php`, `AchievementPolicy.php`, `DocumentPolicy.php`, `LiquidationPolicy.php` |
| `database/migrations/` | Database schema changes | `2026_10_06_010000_make_user_profile_picture_nullable.php`, `2026_10_06_020000_create_officer_terms_table.php`; custom Microsoft, role, and profile migrations; no Articles-table migration exists in this repository |
| `database/seeders/` | Explicit data seeders | `OfficerTermSeeder.php` (run manually after migration) |
| `app/Support/` | Focused school-year calculation and post notification recipient selection | `AcademicYear.php`, `PostNotificationRecipients.php` |
| `app/Notifications/` | Synchronous database notifications for post review and publication | `PostSubmittedForReview.php`, `PostPublished.php`, `PostApproved.php`, `PostRejected.php` |
| `app/Http/Controllers/` | User-scoped notification list, summary, read, and dismiss endpoints | `NotificationController.php` |
| `config/` | Environment and school option configuration | `school.php`, `services.php` |
| `resources/views/` | Public pages, feature pages, dashboards, forms, and UI components | `pages/home.blade.php`; `articles/`, `activities/`, `achievements/`, `documents/`, and `liquidation/` contain feature pages and forms; `layouts/`, `profile/`, `officer/`, `adviser/officers/`, `components/ui/` |
| `resources/views/search/` | Full search results and filters | `index.blade.php` |
| `resources/views/notifications/` | Paginated notification history | `index.blade.php` |
| `bootstrap/` | Framework boot configuration and middleware aliases | `app.php` |

## Shared navbar behavior

The shared header in `resources/views/layouts/app.blade.php` stays sticky while scrolling. Its measured height sets `--site-header-height` in `public/css/app.css`, which also offsets in-page scroll targets. The layout adds the `is-scrolled` class after the page moves down so the header shadow is absent at the top. The header uses z-index 40; account popovers use z-index 50 inside that header, and native dialogs render above both in the browser top layer.

Search suggestions render inside the sticky header with visible overflow and remain below modal dialogs. The existing mobile search icon opens the same search form and suggestion behavior.

- If I want to change navbar pinning, open `public/css/app.css` (`.site-header`).
- If I want to change the header height used by anchor offsets, open `resources/views/layouts/app.blade.php` (measurement) and `public/css/app.css` (`--site-header-height`).
- If I want to change the scroll shadow, open `resources/views/layouts/app.blade.php` (scroll threshold) and `public/css/app.css` (`.site-header.is-scrolled`).
- If I want to change who receives review or publication notifications, open `app/Support/PostNotificationRecipients.php`.
- If I want to change when a post sends a notification, open the matching `app/Http/Controllers/<PostType>Controller.php` store, update, approve, or reject action.
- If I want to change notification text or stored fields, open the matching class in `app/Notifications/`.
- If I want to change notification ownership checks, paging, polling data, read behavior, or actor profile pictures, open `app/Http/Controllers/NotificationController.php` and `routes/features.php`.
- If I want to change the bell dropdown, badge, polling interval, or tab dots, open `resources/views/layouts/app.blade.php` and `public/css/app.css`.
- If I want to change the notifications page layout, open `resources/views/notifications/index.blade.php` and `public/css/app.css`.

## Gotchas and extension notes

- **Custom user key:** `User` uses `user_id`, not Laravel's conventional `id`. Use the model key or explicitly target `user_id` in validation and relationships.
- **Profile pictures:** `profile_picture` is nullable and never mass-assignable. Uploads use generated names in `storage/app/public/profile-pictures`; run `php artisan storage:link`. Missing files fall back to initials. Microsoft login does not fetch photos; existing picture values are never overwritten by the callback.
- **Article primary key:** Articles use `article_id`; user ownership and reviewer relationships target `users.user_id`, not `id`.
- **Article mass assignment:** `Article::$fillable` contains only `title`, `content`, and `image`. Set owner, approval/content status, rejection reason, and review fields in authorized controller actions.
- **Activity and Achievement primary keys:** Activities use `activity_id` and Achievements use `achievement_id`; both relate owners and reviewers through `users.user_id`.
- **Activity and Achievement mass assignment:** Their fillable lists contain only domain fields. Ownership, both statuses, rejection reason, and reviewer fields are set only by trusted controller actions.
- **Activity and Achievement images:** Files use the public disk under `activities/` and `achievements/` with generated names. Run `php artisan storage:link`; missing files use the existing placeholder.
- **Activity and Achievement schemas:** Both tables were created with SQL and have no create/alter migrations in this repository. Check the live schema before changing the database.
- **Article tabs:** The `tab` query value is checked against role-specific allowlists; invalid or hidden tabs fall back to Published. Keep query scopes role-safe when adding a tab.
- **Article images:** Images live on the public disk under `articles/` with generated names. Run `php artisan storage:link` so stored images can be served; missing files use the existing placeholder.
- **Article status rule:** `approval_status` (pending/approved/rejected) records adviser review; `content_status` (active/archived) controls shelving. Restoring never changes approval, and neither field is mass-assignable.
- **Articles schema:** The supplied Articles table is external to the repository migrations; confirm it has the documented columns before deployment.
- **Documents and Liquidation schemas:** These tables are SQL-managed and have no create/alter migrations here. Confirm the live schemas before deployment; the application does not alter them.
- **Private PDF storage:** Document and liquidation PDFs live under `storage/app/private/documents` and `storage/app/private/liquidation` on the local disk. Do not run `storage:link` for these files. Backups and deployments must preserve the private storage directory.
- **PDF upload limits:** `school.max_pdf_size_kb` is the application limit. PHP's `upload_max_filesize` and `post_max_size` in `php.ini` must also be large enough for the desired upload.
- **PDF access:** `school.documents_list_public` controls whether document metadata lists are public; `school.liquidation_list_public` controls liquidation listing access. PDF view/download routes always require login and a completed profile.
- **Role checks:** Blade `@can` checks only hide navigation. Keep middleware or authorization checks on every protected route and write action.
- **Adding a role:** Update the role values and helpers in `User`, the `role` middleware route declarations, Gate definitions, role-specific dashboard redirect, and tests. Review all existing user-management validation before making a new role assignable.
- **Adding an approval-required feature:** Add its schema/model and ownership relationship, policies or equivalent authorization, validated create/update/review endpoints, and status transitions. Set initial status in the server, require a rejection reason, and scope public/student queries to approved and active records only.
- **School options:** Fill the TODO institute and program arrays in `config/school.php`; empty lists intentionally do not allow a student to submit arbitrary values.
- **Officer terms:** The current A.Y. is computed from today's date. Officer name, title, and photo are copied per year; role changes never edit past years. Photos live on the public disk at `storage/app/public/officer-photos` and need `php artisan storage:link`. Officers are never copied forward automatically; use the adviser action when a starting point is wanted.
- **Sticky navbar:** An ancestor with `overflow: auto`, `overflow: hidden`, or `overflow: scroll` can change or block sticky positioning; check the full parent chain before adding such a rule around the shared header.
- **Notifications table:** It was created with SQL, not a project migration. `notifiable_id` is `BIGINT UNSIGNED` while `users.user_id` is `INT UNSIGNED`; the key values fit and Laravel's polymorphic relation uses the custom user key.
- **Notification delivery:** There is no queue worker; sends are synchronous and failures are logged without failing the post action. Broadcast recipients are chunked by `user_id`.
- **Notification links:** A post may be archived or deleted after a notice is sent; opening its stale link returns a friendly message. Tab dots clear when the matching index is visited.
- **Notification polling:** The bell polls every 60 seconds while the tab is visible and the dropdown is closed. Poll errors after logout or session expiry are ignored.
- **Notification JSON filtering:** MariaDB 10.4.32 supports `JSON_EXTRACT` on the notification table's `TEXT` `data` field; the shared layout computes all post-type dot counts in one grouped query.

## Articles tab

Students and guests see Published articles only. Officers also see My Posts and their own Archived articles; advisers see Pending Review, Rejected, Archived, and All. `approval_status` tracks review while `content_status` tracks active or archived visibility. When adding a tab, add it to the role allowlist in `ArticleController::index`, query with the Article scopes, and keep public/student results approved AND active.

## Activities and Achievements tabs

Activities and Achievements use the same role-specific tabs, search, and ten-item pagination as Articles. Change their tab rules in `ActivityController::index` or `AchievementController::index`; public lists and home cards must use each model's `publiclyVisible()` scope. Their date, location, awardee, and category fields are feature-specific domain data.

## Documents and Liquidation tabs

Documents and Liquidation use the Article role-specific tabs, title search, and ten-item pagination. Documents also filters by category. Public/student queries must use each model's `publiclyVisible()` scope. PDF view and download handlers authorize before reading from the private local disk; missing files return a friendly message. Change list access and maximum size in `config/school.php`.
