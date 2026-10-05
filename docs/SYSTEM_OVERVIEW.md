# SSITE System Overview

SSITE is a Laravel Blade application for the Student Society in Information Technology Education at Mabalacat City College. Users sign in with Microsoft or the existing password login, then complete required student information before using protected pages. The app stores identity, role, and student information on one `users` table with `user_id` as its primary key. Articles have a separate posting, review, and archiving workflow.

## Roles

| Role | Intended capabilities |
| --- | --- |
| Student | View published (approved and active) articles and edit allowed personal information. |
| Officer | Student capabilities, plus post articles, edit and archive/restore their own articles; new articles start pending. |
| Adviser | Officer capabilities, plus review all articles, approve/reject, archive/restore any article, permanently delete, bulk archive, and manage non-adviser user roles. |

The current code enforces dashboard and adviser route roles, and adviser role changes are implemented. Article posting, ownership checks, approval, rejection, archive/restore, and approved-only queries are implemented for the existing `articles` table.

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
3. If no local account exists, the callback creates a student account with Microsoft-provided name/email and server-set authentication fields.
4. The user is logged in. If `profile_completed_at` is null, the callback sends them to `profile.complete`; otherwise, it redirects to an intended URL or the route selected for their role.
5. `EnsureProfileCompleted` redirects an incomplete account away from protected routes. The completion form and logout are exempt so the user can finish the form or leave.
6. `ProfileController` validates and saves the signed-in user's allowed profile fields, sets `profile_completed_at`, and redirects onward.

`config/school.php` controls the school email domain, profile enforcement flag, allowed gender/year values, student-number format, and TODO institute/program lists.

## Post approval flow

The Articles workflow is:

1. An officer creates an article owned by that officer. The server sets `approval_status=pending` and `content_status=active`; advisers can post directly approved by the school setting.
2. The adviser reviews pending articles and either approves them or rejects them with a required reason.
3. Public/student-facing queries return only `approval_status=approved` AND `content_status=active` articles.
4. Archiving changes only `content_status` to `archived`; restoring changes it to `active` and preserves approval state, so a pending or rejected article does not become published.
5. Officers can edit and archive/restore only their own articles; advisers can manage all articles, bulk archive, and permanently delete.

The database table is managed outside this repository's migrations. Adviser review for Articles is provided by the Articles tabs; the legacy adviser review and dashboard pages still contain placeholder content for other post types.

## Folder map

| Location | Purpose | Important files |
| --- | --- | --- |
| `routes/` | URL definitions grouped by feature and access role | `web.php`, `auth.php`, `profile.php`, `officer.php`, `adviser.php`, `features.php` |
| `app/Http/Controllers/` | Login, profile, dashboard, adviser, and article request handling | `ArticleController.php`, `Auth/`, `Profile/`, `Officer/`, `Adviser/` |
| `app/Http/Middleware/` | Authentication, role, and profile-completion gates | `EnsureProfileCompleted.php`, `RoleMiddleware.php` |
| `app/Http/Requests/` | Validation and authorization grouped by feature | `Article/`, `Profile/` |
| `app/Models/` | User and article persistence and role helpers | `User.php`, `Article.php` |
| `app/Policies/` | Per-record authorization for article visibility and actions | `ArticlePolicy.php` |
| `database/migrations/` | Database schema changes | The custom Microsoft, role, and profile migrations; no Articles-table migration exists in this repository |
| `config/` | Environment and school option configuration | `school.php`, `services.php` |
| `resources/views/` | Public pages, feature pages, dashboards, forms, and UI components | `pages/`, `layouts/`, `articles/`, `profile/`, `officer/`, `adviser/`, `components/ui/` |
| `bootstrap/` | Framework boot configuration and middleware aliases | `app.php` |

## Gotchas and extension notes

- **Custom user key:** `User` uses `user_id`, not Laravel's conventional `id`. Use the model key or explicitly target `user_id` in validation and relationships.
- **Article primary key:** Articles use `article_id`; user ownership and reviewer relationships target `users.user_id`, not `id`.
- **Article mass assignment:** `Article::$fillable` contains only `title`, `content`, and `image`. Set owner, approval/content status, rejection reason, and review fields in authorized controller actions.
- **Article tabs:** The `tab` query value is checked against role-specific allowlists; invalid or hidden tabs fall back to Published. Keep query scopes role-safe when adding a tab.
- **Article images:** Images live on the public disk under `articles/` with generated names. Run `php artisan storage:link` so stored images can be served; missing files use the existing placeholder.
- **Article status rule:** `approval_status` (pending/approved/rejected) records adviser review; `content_status` (active/archived) controls shelving. Restoring never changes approval, and neither field is mass-assignable.
- **Articles schema:** The supplied Articles table is external to the repository migrations; confirm it has the documented columns before deployment.
- **Role checks:** Blade `@can` checks only hide navigation. Keep middleware or authorization checks on every protected route and write action.
- **Adding a role:** Update the role values and helpers in `User`, the `role` middleware route declarations, Gate definitions, role-specific dashboard redirect, and tests. Review all existing user-management validation before making a new role assignable.
- **Adding an approval-required feature:** Add its schema/model and ownership relationship, policies or equivalent authorization, validated create/update/review endpoints, and status transitions. Set initial status in the server, require a rejection reason, and scope public/student queries to approved and active records only.
- **School options:** Fill the TODO institute and program arrays in `config/school.php`; empty lists intentionally do not allow a student to submit arbitrary values.

## Articles tab

Students and guests see Published articles only. Officers also see My Posts and their own Archived articles; advisers see Pending Review, Rejected, Archived, and All. `approval_status` tracks review while `content_status` tracks active or archived visibility. When adding a tab, add it to the role allowlist in `ArticleController::index`, query with the Article scopes, and keep public/student results approved AND active.
