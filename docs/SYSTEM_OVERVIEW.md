# SSITE System Overview

SSITE is a Laravel Blade application for the Student Society in Information Technology Education at Mabalacat City College. Users sign in with Microsoft or the existing password login, then complete required student information before using protected pages. The app stores identity, role, and student information on one `users` table with `user_id` as its primary key. Officers and advisers have role-specific dashboards, but the post storage and approval workflow is currently a UI placeholder rather than a working feature.

## Roles

| Role | Intended capabilities |
| --- | --- |
| Student | View shared site content and edit allowed personal information. |
| Officer | Student capabilities, plus create and manage their own posts; new posts should start as pending. |
| Adviser | Officer capabilities, plus approve or reject posts and manage non-adviser user roles. |

The current code enforces dashboard and adviser route roles, and adviser role changes are implemented. Post creation, ownership checks, approval, rejection, and approved-only post queries are not implemented because this repository has no post models, tables, feature controllers, or policies.

## Request flow example

An adviser opening `/adviser/users` follows this path:

1. `routes/web.php` matches the named `adviser.users.index` route.
2. Laravel runs `auth`, `role:adviser`, and `profile.complete` middleware.
3. `App\Http\Controllers\Adviser\UserController::index` validates filters and queries users through the `User` model.
4. The controller renders `resources/views/adviser/users/index.blade.php`, which uses shared components from `resources/views/components`.

Profile editing uses the same authenticated-user pattern: the profile controller obtains the user from the request session rather than accepting a user ID from the form.

## Microsoft login and forced profile completion

1. The user starts Microsoft OAuth at `/auth/microsoft`; Socialite returns to the callback.
2. `MicrosoftAuthController` finds the local account by Microsoft ID, or links a matching email account if it is not already linked.
3. If no local account exists, the callback creates a student account with Microsoft-provided name/email and server-set authentication fields.
4. The user is logged in. If `profile_completed_at` is null, the callback sends them to `profile.complete`; otherwise, it redirects to an intended URL or the route selected for their role.
5. `EnsureProfileCompleted` redirects an incomplete account away from protected routes. The completion form and logout are exempt so the user can finish the form or leave.
6. `ProfileController` validates and saves the signed-in user's allowed profile fields, sets `profile_completed_at`, and redirects onward.

`config/school.php` controls the school email domain, profile enforcement flag, allowed gender/year values, student-number format, and TODO institute/program lists.

## Post approval flow

The intended workflow is:

1. An officer creates a post owned by that officer. The server sets its initial status to `pending`.
2. The adviser reviews the pending post and either approves it or rejects it with a required reason.
3. Public/student-facing post queries return only posts with `approved` status.
4. Officers can manage only posts they own; advisers can review all posts.

This flow is **not implemented yet** in the current repository. Adviser review and officer dashboard pages render empty collections and disabled actions, marked with TODOs. Add post models/tables, server-side ownership and approval authorization, and approved-only queries before enabling those actions.

## Folder map

| Location | Purpose | Important files |
| --- | --- | --- |
| `routes/` | URL definitions and route middleware | `web.php` |
| `app/Http/Controllers/` | Login, profile, dashboard, and adviser request handling | `MicrosoftAuthController.php`, `ProfileController.php`, `Adviser/UserController.php` |
| `app/Http/Middleware/` | Authentication, role, and profile-completion gates | `EnsureProfileCompleted.php`, `RoleMiddleware.php` |
| `app/Http/Requests/` | Validation and authorization for profile forms | `ProfileRequest.php`, `StoreProfileRequest.php`, `UpdateProfileRequest.php` |
| `app/Models/` | User persistence and role helpers | `User.php` |
| `database/migrations/` | Database schema changes | The custom Microsoft, role, and profile migrations |
| `config/` | Environment and school option configuration | `school.php`, `services.php` |
| `resources/views/` | Shared layout, dashboards, profile forms, and Blade components | `layouts/app.blade.php`, `profile/`, `adviser/`, `officer/`, `components/` |
| `bootstrap/` | Framework boot configuration and middleware aliases | `app.php` |

## Gotchas and extension notes

- **Custom user key:** `User` uses `user_id`, not Laravel's conventional `id`. Use the model key or explicitly target `user_id` in validation and relationships.
- **Mass assignment:** `role` is excluded from `User::$fillable`; set it only in trusted server-side role assignment or account creation. There is no post model yet, so when one is added, keep `status` and `rejection_reason` out of user-facing mass assignment and set them in authorized controller actions.
- **Role checks:** Blade `@can` checks only hide navigation. Keep middleware or authorization checks on every protected route and write action.
- **Adding a role:** Update the role values and helpers in `User`, the `role` middleware route declarations, Gate definitions, role-specific dashboard redirect, and tests. Review all existing user-management validation before making a new role assignable.
- **Adding an approval-required feature:** Add its schema/model and ownership relationship, policies or equivalent authorization, validated create/update/review endpoints, and status transitions. Set initial status to pending in the server, require a rejection reason, and scope public/student queries to approved records only.
- **School options:** Fill the TODO institute and program arrays in `config/school.php`; empty lists intentionally do not allow a student to submit arbitrary values.
