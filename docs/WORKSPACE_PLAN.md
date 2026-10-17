# SSITE Workspace Plan

**Status:** Phase 1 audit and Phase 2 plan. No implementation is approved or started.
**Prepared:** 2026-10-17
**Target:** A separate role-based workspace shell for content management and administration, alongside the existing public site.

## Scope and evidence

- Read `PRODUCT.md`, the existing workspace and public layouts/styles, workspace components and views, officer/adviser dashboards, all five content controllers and policies, role/capability configuration, route files, profile/notification surfaces, and the existing design/workflow documentation (`POSITIONS_PLAN`, `DESIGN_UPGRADE`, `FEED_DESIGN`, `SORT_FILTER_PLAN`, `RECOMMENDATIONS`, `SYSTEM_OVERVIEW`, and `WHERE_IS_WHAT`, including their available progress files).
- `docs/WORKSPACE_PROGRESS.md` and an earlier `docs/WORKSPACE_PLAN.md` do not exist. Treat this as a new plan, while preserving existing source and uncommitted work.
- Captured `php artisan route:list --json` to `%TEMP%\SSITECMS-route-list-before.json`. The snapshot and a fresh route list both contain **108 entries** with identical URI, method, name, action, and middleware values. No route changes are planned.
- No browser automation tool is available in this session. Existing screenshots supplied in the conversation show the President workspace and the public student site; other role-only screens could only be reviewed in source. Do not represent responsive or interactive behavior as browser-verified.
- No tests, migrations, seeders, database reads/writes, cache refreshes, package installs, or source edits were performed for this plan.
- Existing plans overlap this work: `POSITIONS_PLAN.md` defines the authorization boundaries; `DESIGN_UPGRADE.md` records public-site tokens and dashboard work already applied; `FEED_DESIGN.md` and `SORT_FILTER_PLAN.md` define the current public feed and controls. Those behaviors remain in place.

## Existing role capabilities to preserve

Capabilities are resolved from `config/school.php`, `User`, the registered Gates, policies, and route middleware. The workspace is a presentation layer and will not grant access.

| Role / position | Existing capability source | Workspace scope proposed |
|---|---|---|
| President | `canReview()`, `canManageOfficers()`, all five `canPostIn()` checks; President policies | Review all modules; create and manage content; manage public officer terms; see all status counts. |
| Secretary | `canPostIn('documents')`; own-item policy checks | Create Documents and manage only permitted own submissions. |
| Auditor | `canPostIn('liquidations')`; own-item policy checks | Create Liquidation reports and manage only permitted own submissions. |
| Multimedia | `canPostIn('articles'|'achievements'|'activities')`; own-item policy checks | Create and manage only permitted own submissions in those modules. |
| Adviser | `canAssignOfficers()` / `manage-users`; adviser role middleware | Assign student/officer roles and configured positions; see existing count-only pending summaries. No post controls. |
| Superadmin | `canAssignAdvisers()` / `manage-users`; superadmin role middleware | Assign/revoke adviser roles; see officer-position counts and optional role history. No post controls. |
| Student, guest, capability-less officer | No workspace capability | Public layout only; no workspace links, markup, or workspace counts. |

**Authorization conflict to resolve before implementation:** the requested adviser sidebar mentions an “Officer roster” for About-page entries. Current source gives that capability only to the President (`canManageOfficers()` and `can:manage-officers`); adviser authority covers role and position assignment through `/adviser/users`, not officer-term roster changes. This plan keeps the roster President-only to honor the visual-only/no-permission-change constraint. Granting advisers roster management would require a separate authorization decision and is outside this plan.

## Page and route inventory

“Current layout” describes the current implementation before this plan. The planned shell decision is in the next section.

| Audience / role | URL and route name | Controller/view | Current layout and capability |
|---|---|---|---|
| Public, students, and signed-in users | `/articles`, `articles.index`; `/activities`, `activities.index`; `/achievements`, `achievements.index`; `/documents`, `documents.index`; `/liquidation`, `liquidations.index` | Matching content controller `index`; matching public `<module>.index` view | `layouts.app`; public published visibility and configured list access. Tab access is validated in each controller. |
| President editorial lists | `/editorial/articles`, `/editorial/activities`, `/editorial/achievements`, `/editorial/documents`, `/editorial/liquidations`; `editorial.<module>.index` | Matching content controller `index`; `workspace.posts.<module>.index` | `layouts.app` currently swaps in the workspace partial for the President. Routes retain `auth`, officer, profile-complete, and `can:review-posts` middleware. |
| President dashboard | `/officer/dashboard`, `officer.dashboard` | `Officer\DashboardController`; `workspace.dashboard` | `layouts.app` with workspace partial for President. Officer role and profile completion middleware. |
| Secretary, Auditor, Multimedia dashboards | `/officer/dashboard`, `officer.dashboard` | `Officer\DashboardController`; `officer.dashboard` | `layouts.app`; role and profile completion middleware. Dashboard data is constrained by `postableModules()` and owner `user_id`. |
| Adviser and Superadmin dashboards | `/adviser/dashboard`, `adviser.dashboard` | `Adviser\DashboardController`; `adviser.dashboard` | `layouts.app`; `role:adviser,superadmin`, profile completion. Dashboard differs by `isAdviser()` / `isSuperadmin()`. |
| President review queue | `/adviser/reviews`, `adviser.reviews.index` | `Adviser\ReviewController@index`; `adviser.reviews.index` view | `layouts.app` with President workspace partial; `auth`, officer, profile completion, `can:review-posts`. |
| President public officer-term manager | `/adviser/officers` and create/edit/write actions; `adviser.officers.*` | `Adviser\OfficerTermController`; `adviser.officers.*` views | `layouts.app` with President workspace partial; `can:manage-officers`. |
| Adviser user and position assignment | `/adviser/users`, `/adviser/users/{user}` and `PUT /adviser/users/{user}/role`; `adviser.users.*` | `Adviser\UserController`; `adviser.users.index/show` | `layouts.app`; adviser role middleware and `can:changeRole,user`. Adviser may assign student/officer roles and configured positions under the existing policy. |
| Superadmin adviser assignment | Same `/adviser/users` family and route names | Same controller/views with superadmin branch | `layouts.app`; superadmin role middleware and `can:changeRole,user`. Can assign/revoke advisers only under current policy. |
| Content create/edit forms | `/articles/create`, `/activities/create`, `/achievements/create`, `/documents/create`, `/liquidation/create` and matching `*.create`; matching `/{id}/edit` and `*.edit` | Matching content controller; `<module>.create/edit` and shared `<module>._form` | `layouts.app`; currently the President receives the workspace partial, other authorized posters receive the public layout. `can:create` / `can:update` route middleware and policy checks. |
| Content details and actions | Matching `/{id}` and `<module>.show`; action routes `<module>.approve/reject/archive/restore/destroy` | Matching controller and `<module>.show` | `layouts.app`; private-item access and actions remain policy/owner scoped. No route or permission change proposed. |
| Notifications | `/notifications`, `notifications.index`, plus existing read/dismiss/avatar routes | `NotificationController`; `notifications.index` and existing account scripts | `layouts.app`; authenticated/profile-complete and recipient-scoped behavior. |
| Profile | `/profile/edit`, `profile.edit`, and existing profile/picture writes | `ProfileController`; profile views | `layouts.app`; authenticated self-service. |
| Public account Manage section | One occurrence in `resources/views/layouts/app.blade.php` around the current account dropdown | Blade account dropdown | Currently shows Dashboard, Review Posts, Manage Users/Advisers, and Manage About Us independently. Replace the section with one capability-gated “Open workspace” link; keep Profile, Settings, Logout, and the public navigation behavior. |

## Eight workspace decisions

These defaults follow the attached request and are the proposed plan until the user changes them.

| # | Decision | Proposed answer |
|---:|---|---|
| 1 | Include Secretary? | **Yes.** Secretary gets a workspace through the existing Documents capability. Other officers without capabilities remain on the public site. |
| 2 | Management page URLs | **Keep existing URLs and names.** Select the layout in the existing controller/view path according to the validated tab and the viewer’s existing capability. Do not add `/workspace/*` routes. |
| 3 | Add a workspace hub route? | **No.** Use each role’s existing dashboard route. |
| 4 | Remember collapsed sidebar state? | **Yes**, with localStorage inside `try/catch`; no-JavaScript default remains expanded and usable. |
| 5 | Workspace brand name | **SSITE Workspace.** |
| 6 | Put create/edit forms in workspace? | **Yes** for users already authorized to create/edit. Keep their existing validation, actions, uploads, and HTTP methods. |
| 7 | Post-login destination | **Existing role dashboard.** Keep `User::dashboardRoute()` and existing auth redirects; ensure workspace-eligible roles see the workspace shell at those dashboards. |
| 8 | Desktop collapse and phone navigation | **Icon rail on desktop; off-canvas drawer on phones.** Drawer uses overlay, focus handling, Escape, scroll lock, and at least 44px targets. |

## Workspace shell wireframes

The reference screenshot contributes only the general arrangement. The shell uses SSITE assets and the palette below; it will not use the other product’s name, copy, colors, decorative effects, or exact proportions.

### Expanded desktop sidebar

```text
┌──────────────────────┬─────────────────────────────────────────────────┐
│ SSITE logo           │ [collapse]  Section / Page      View site  Bell │
│ SSITE Workspace      │                               [avatar/name ▾]  │
│                      ├─────────────────────────────────────────────────┤
│ Signed in as         │                                                 │
│ [avatar] Name        │  Page title          One-line purpose           │
│ Position / role      │  Primary action                                 │
│                      │                                                 │
│ OVERVIEW             │  Counts, review queue, or focused work list     │
│ Dashboard            │                                                 │
│ CONTENT / REVIEW     │                                                 │
│ Capability actions   │                                                 │
│ ...                  │                                                 │
│                      │                                                 │
│ View site            │                                                 │
│ Logout               │                                                 │
└──────────────────────┴─────────────────────────────────────────────────┘
```

### Collapsed desktop rail

```text
┌─────────┬─────────────────────────────────────────────────────────────┐
│ SSITE   │ [expand]  Section / Page       View site  Bell  [profile]    │
│ [icons] ├─────────────────────────────────────────────────────────────┤
│ [icon]  │                                                             │
│ [icon]  │                 Same content and page width                 │
│ [icon]  │                                                             │
│         │                                                             │
│ [site]  │                                                             │
└─────────┴─────────────────────────────────────────────────────────────┘
```

Every icon-only item gets an accessible name and keyboard-operable tooltip; active state includes a shape/marker and `aria-current="page"`, not color alone.

### Phone drawer

```text
┌──────────────────────────────────┐
│ [menu] Page title    Bell [avatar]│
├──────────────────────────────────┤
│                                  │
│           Current page           │
│                                  │
└──────────────────────────────────┘
   overlay + drawer from left
   logo, signed-in identity, grouped links, View site, Logout
```

The drawer opens from a top-bar button, traps focus while open, closes on Escape/overlay/link navigation, returns focus to its trigger, and locks background scrolling. JavaScript enhancement is applied only after initialization so the links remain reachable if JavaScript is unavailable.

## Capability-filtered sidebar configuration

Create one presentation-only `config/workspace.php` array for groups, labels, icon names, route names, and a capability/Gate key per item. Filter it with the existing `User` methods/Gates before rendering. Do not use this config as authorization and do not query from Blade.

| Role | Sidebar sections and items | Count source and visibility |
|---|---|---|
| President | Overview: Dashboard. Review: Pending review and per-module pending links. Content: Articles, Activities, Achievements, Documents, Liquidation, each with New and management list. Administration: Officer roster. Footer: View site, Notifications, Profile, Logout. | One grouped aggregate per module for pending/approved/archived totals, visible only to the President. Review totals link to the existing review route/tab. Notification badge uses the existing signed-in-user unread summary. |
| Secretary | Overview: Dashboard. Documents: New document, My submissions, Archived. Footer: View site, Notifications, Profile, Logout. | One aggregate for the Secretary’s own Documents, filtered by `created_by_user_id = user_id`; show own pending/approved/rejected counts only. |
| Auditor | Overview: Dashboard. Liquidation: New report, My submissions, Archived. Footer: View site, Notifications, Profile, Logout. | One aggregate for the Auditor’s own Liquidation records, filtered by custom owner/user keys; show only own counts. |
| Multimedia | Overview: Dashboard. Articles, Achievements, Activities groups, each with New, My submissions, Archived. Footer: View site, Notifications, Profile, Logout. | One aggregate per allowed module for the signed-in owner only. |
| Adviser | Overview: Dashboard. Accounts: Officers and Positions (existing `/adviser/users` assignment manager). Footer: View site, Notifications, Profile, Logout. | Pending counts only, grouped by module; no post titles, authors, excerpts, or private content. Existing role history is read only if its table/schema exists. No About roster link under current capabilities. |
| Superadmin | Overview: Dashboard. Access: Advisers (existing `/adviser/users` assignment manager), Role history when available. Footer: View site, Notifications, Profile, Logout. | Officer-position totals and adviser totals from existing user rows; optional role history from `role_changes`. Do not expose pending titles or post content. |

Counts for the shell/dashboard are read-only presentation data, selected once per module per request and reused. Use a single grouped aggregate query per module with capability/ownership scopes and only the required status columns. Adviser pending aggregates contain counts only. The current notification composer’s grouped unread query and recipient scoping remain unchanged. Do not duplicate a page’s existing list query just to draw a count, and do not fetch rows solely for nav badges.

## Role dashboard wireframes

All dashboards use one page frame, a short greeting, one role label, and a compact status area. Use up to four clickable stat cards, then the most actionable work list. Empty states name the next permitted action. Cards only link to existing routes with the validated tab/filter they summarize.

| Role | Dashboard content |
|---|---|
| President | Welcome strip with Review queue and one primary create action. Four cards at most: pending review, approved this month, rejected awaiting resubmission, archived. “Needs your review” shows latest pending records across modules with author, type, age, and Review link. Recent decisions use existing review timestamps/actors only. |
| Secretary | Documents status cards: pending, published, rejected. “Needs your attention” lists the Secretary’s rejected documents with reason and a permitted View/Edit action. Primary action: New document. |
| Auditor | Same pattern for the Auditor’s own Liquidation records; amounts appear only where the existing module view already exposes them. |
| Multimedia | Combined own-post status cards and a rejected-items list across Articles, Achievements, and Activities. A clear create action for each existing module capability. |
| Adviser | Officer counts by configured position; strong vacancy warning if no President exists; pending counts per module only; recent role changes only if available. Primary action opens existing officer role/position assignment. No post details/actions. |
| Superadmin | Current adviser assignments, officer counts by configured position, and recent role changes when available. Primary action opens existing adviser assignment. No post details/actions. |

No cards, routes, or controls imply new drafts, scheduling, comments, analytics, calendar, permissions, or activity logs. “Approved this month” / “recent decisions” may use only existing fields and must be checked for current data/schema support before coding; if a field is absent, omit that card instead of adding schema.

## Management pages

### Lists

- Render every management tab in the workspace for eligible users; keep the existing public Published tab/feed in `layouts.app`.
- Use compact rows with a small image/file marker, title, module/date, status where it varies, author for review lists, and one clear primary action plus the existing secondary-action menu. Keep existing filters and sort controls in one compact row and preserve query strings.
- On phones, convert rows to stacked cards; keep actions at 44px minimum, preserve visible focus, and avoid horizontal page scrolling. Retain existing empty states where appropriate and add role-specific next actions only where they do not create new capabilities.
- Continue to render action controls only through the existing `@can` and capability checks. Preserve CSRF, method spoofing, escaped output, private PDF delivery, custom model keys, and owner comparisons using `created_by_user_id` and `user_id`.

### President review

- Keep the existing `/adviser/reviews` route and middleware. Render the queue inside the workspace with module/status filters already supported.
- Provide an in-page preview using the selected post’s existing fields/media and existing show-page rules. Approve/reject actions continue through current routes; reject requires the existing reason and only pending items can be reviewed. The author’s rejected-item view continues to show the reason.
- Do not add a new preview endpoint, change review eligibility, or expose private records to non-reviewers.

### Create and edit forms

- Put all authorized create/edit forms inside the shared workspace frame. Keep the existing title-first fields and validation messages, preserved old input, image preview, PDF name/size feedback, and current server-side validation/upload behavior.
- Show required markers based on the existing rules only. Give Save/Submit states clear labels. An optional vanilla-JS unsaved-change warning must not block submission or make the form unusable without JavaScript.
- Keep every existing `@csrf`, HTTP method, route, upload throttle, and validation rule. No field, `$fillable`, model, or database changes.

### User, notification, and profile pages

- Keep adviser/superadmin user assignment tables and the President officer-term manager behavior; on phones, stack rows/cards. Do not add email, student number, or contact details to the workspace identity chrome. Role/position assignment remains policy checked.
- Reuse the existing notification routes, unread query, account scripts, and profile self-service views. Present these pages in the workspace shell for workspace roles in the final navigation batch; keep all behavior and data scoping intact.

## Layout selection and route expectation

Create `resources/views/layouts/workspace.blade.php` as a separate shell. Leave `layouts.app` as the public site layout, except for replacing its account dropdown Manage section with a capability-gated **Open workspace** link. The public navigation pill, search, feed cards, sticky behavior, nav dots, and student/guest markup remain as they are.

For the existing module `index` actions, controllers already validate each role’s allowed tab. After that validation, pass a simple layout choice to the matching view:

- `tab=published` uses the existing public feed and `layouts.app` for every role.
- An already-authorized management tab (`mine`, `pending`, `rejected`, `archived`, `all`, as supported by that controller and role) uses `layouts.workspace` for roles who can access it.
- Existing create/edit actions use the workspace layout for their authorized poster roles.
- Existing dashboard/manager actions use the workspace layout only for workspace-eligible roles.
- Invalid or unauthorized tabs continue to fall back or deny exactly as the current controller/policy does; choosing a layout never authorizes a request.

The five existing `/editorial/*` named routes remain in place with their current President-only middleware as compatibility entry points. Workspace navigation for all roles uses the existing module URLs and validated tab query values. Existing route paths, names, methods, actions, and middleware remain unchanged. **Expected `route:list` diff: empty (108 entries).** Any implementation proposal that needs a new path or middleware change returns to the user for approval before coding.

## Design tokens and layout measurements

Use the current SSITE blue/navy palette rather than the unrelated reference palette. The current `PRODUCT.md` says deep green/gold, which conflicts with this latest explicit brief, the supplied public-site screenshot, and active `--ssite-*` tokens. This workspace plan follows the latest brief and current site tokens; do not change `PRODUCT.md` during this phase.

| Token | Proposed value or mapping | Use |
|---|---|---|
| `--workspace-sidebar-bg` | `var(--ssite-navy)` (`#17324D`) | Desktop sidebar; white text/icons and a visible accent/shape for active item. |
| `--workspace-topbar-bg` | `var(--ssite-blue)` (`#8AD5F4`) | Compact workspace top bar. |
| `--workspace-ground` | `#fff`; cards use `var(--ssite-blue-light)` (`#C7ECF9`) selectively | Calm work surface; subtle grid only behind content, never behind nav/topbar. |
| `--workspace-accent` / line | `var(--ssite-line)` (`#3DAFEA`) | Focus, section rule, small active marker; pair with non-color cue. |
| `--workspace-text` / secondary | `var(--ssite-navy)` / `var(--ssite-navy-soft)` | Text and muted labels. Verify contrast for every state before implementation; if an accessible adjustment would alter the named palette materially, ask first. |
| Body/display typography | Existing Poppins body and current display stack (`Universal`, then Poppins/system fallback) | Keep the incumbent type. Source has no bundled `Universal` font or `@font-face`; actual pixel-style rendering is unverified. Do not download/install a font without approval. |
| Sidebar width / rail | `16.5rem` expanded; `4.5rem` collapsed | Existing shell is 264px; rail is a proposed new state. |
| Top bar | `4.25rem` minimum height | Existing workspace top bar is 68px. Keep one row with responsive truncation. |
| Content width / gutter | `76rem` max; `clamp(1rem, 3vw, 2.5rem)` inline padding | Dense management screens with comfortable reading width. |
| Tap targets / gaps | At least `44px`; at least `8px` between interactive controls | Sidebar, top bar, forms, row actions. Current 42px links and 36–40px controls need to grow. |
| Layer order | Map to shared dropdown/header/floating/search/notification tokens; workspace drawer overlay above page; modal above drawer | Keep dropdowns, bell, menus, drawer, and modals above sticky/card stacking contexts. Record exact values in workspace token definitions during Batch 1. |
| Motion | `150–200ms`, transform/opacity; use `prefers-reduced-motion` alternative | Sidebar collapse and drawer only; hover lift only on hover-capable devices. |

No gradients, glass effects, external icon package, external font fetch, or new brand assets. Use the existing SSITE logo, existing workspace icon component, and restrained offset-shadow cards from the current public design system.

## Audit before implementation

### Design critique (source review plus supplied screenshots)

Heuristic scale is 0–4. Total: **23/40**. The assessment was static/source-based; no live browser rendering was available.

| # | Heuristic | Score | Main observation |
|---:|---|---:|---|
| 1 | Visibility of system status | 3 | Counts/statuses and unread notifications orient users. |
| 2 | Match with real world | 3 | Review, roster, and submission language matches SSITE workflows. |
| 3 | User control and freedom | 2 | Navigation is available, but some counts lack a direct next step. |
| 4 | Consistency and standards | 2 | President workspace, officer dashboard, and adviser dashboard use different shells/hierarchies. |
| 5 | Error prevention | 2 | Role-filtered links help, but consequential steps and recovery cues are uneven. |
| 6 | Recognition rather than recall | 3 | Role labels and status markers help; paired library-link/select controls are not self-explanatory. |
| 7 | Flexibility and efficiency | 2 | Quick-create exists; some role counts lack a direct filtered destination. |
| 8 | Aesthetic and minimalist design | 3 | Workspace is restrained; officer dashboard repeats status across cards and dense tables. |
| 9 | Error recovery | 2 | President-vacancy warning is useful; other failure guidance is sparse. |
| 10 | Help and documentation | 1 | Brief page descriptions, little contextual guidance. |

**Design-specificity verdict:** Role responsibilities and SSITE content make the information architecture product-specific. The shell is orderly but still feels like a generic admin surface, and the existing role dashboards do not yet form one coherent workspace. The newest brief sets the brand direction: navy sidebar, light-blue top bar, SSITE imagery, existing type and spacing. This is consistent with current CSS and the supplied public-site screenshot; `PRODUCT.md`’s older green/gold note is stale for this request.

**Strengths:** Role-aware navigation and dashboard content; actionable review/rejected states; existing mobile menu, Escape handling, visible focus treatment, and link back to the public site.

**Priority issues:**

1. **P1 – Workspace is President-only.** Other content roles and administrators remain in the public layout or separate dashboard layout, so the planned work surface is fragmented.
2. **P1 – Closed mobile navigation can remain keyboard reachable.** The drawer is visually translated off-screen, while its links are not made inert/hidden and focus is not trapped on open. Keyboard users may tab into invisible links.
3. **P2 – Small interactive targets.** Current sidebar links are 42px high; menu toggle is 38px; notification control becomes 36px on narrow screens. The brief requires at least 44px.
4. **P2 – Repeated status summaries.** Officer dashboards repeat module status in cards, a table, and recent posts, increasing scanning effort.
5. **P2 – Library controls are ambiguous.** Each Published/Archived row combines a link with a separate module selector; clearer control grouping and labels are needed.

Persona questions to carry into design review: Can authors open a rejected item directly for editing? Which review indicators help the President prioritize? How should an adviser interpret a high pending count when only the President can act?

### Technical audit

Audit dimensions are 0–4. Total: **12/20 (Acceptable; substantial work remains for accessibility and responsive targets).**

| Dimension | Score | Evidence |
|---|---:|---|
| Accessibility | 2 | Good landmarks/focus styles exist, but mobile drawer focusability and sub-44px controls need work. |
| Performance | 3 | Existing notification summary is grouped and page lists paginate; workspace/dashboard count paths need consolidation and reuse. |
| Theming | 2 | Shared palette tokens exist, but workspace styles include many hard-coded colors and no unified workspace token set. |
| Responsive design | 2 | Breakpoints and stacked stat layouts exist; target sizes and closed-drawer keyboard behavior are incomplete. |
| Implementation integrity | 3 | Role-based components and policies are coherent; views/layout selection are split between public and President-specific workspace paths. |

The Impeccable deterministic detector returned **0 findings** for the audited view files. Manual source checks found the drawer/touch-target concerns above. Browser inspection and role-specific screenshot review were unavailable; no live visual or touch result is claimed.

## Files proposed for implementation after approval

No files in this section are changed by this plan. Final filenames can be adjusted in each separately approved batch.

### Create

- `resources/views/layouts/workspace.blade.php` — separate shared workspace layout.
- `config/workspace.php` — presentation-only sidebar groups and route/icon metadata with existing Gate/capability keys.
- `docs/WORKSPACE_PROGRESS.md` — only after the first implementation batch is approved; progress record after every approved batch.

### Shared files to touch

- `resources/views/layouts/app.blade.php` — replace only the Manage account-dropdown group with “Open workspace”; remove workspace-shell switching so it remains the public shell.
- `resources/views/layouts/partials/workspace.blade.php` — extract/refactor current shell markup for the new layout; preserve accessible labels and existing identity data.
- `resources/views/layouts/partials/account-scripts.blade.php` and existing notification/account components only as needed to reuse the current bell behavior.
- `public/css/workspace.css` — workspace-specific tokens, responsive shell, icon rail, drawer, content grid, focus, contrast, and reduced-motion rules. Do not recolor public pages.
- `resources/views/components/workspace/**` — reuse current icon, list/card, workflow, confirmation, and empty-state components; adjust for the new shell as needed.

### Controllers touched (display data and view selection only)

- `ArticleController`, `ActivityController`, `AchievementController`, `DocumentController`, and `LiquidationController` — after existing tab validation, provide a layout choice and any minimal read-only display aggregates. Do not modify visibility/ownership filters or authorization.
- `Officer\DashboardController` and `Adviser\DashboardController` — supply role-specific display data only, reusing existing capability branches.
- `Adviser\ReviewController` — only if needed to pass preview presentation data from its already authorized records; preserve existing review scope/actions.
- `Adviser\UserController` — only if layout selection/display formatting needs to be passed; keep current role assignment policy and fields.
- `AppServiceProvider` — only if the shared workspace count/unread display data needs a request-scoped composer; preserve current unread aggregation and visibility.
- Profile/notification controllers only if the existing page needs a layout selector passed; no endpoint, query scope, or mutation change.

### Views to touch

- Existing `workspace/dashboard`, `officer/dashboard`, `adviser/dashboard`, `adviser/reviews`, `adviser/users`, and `adviser/officers` views.
- The five public module `index` views and five existing `workspace/posts/*/index` views, plus the five shared form partials/create/edit wrappers.
- Existing notification and profile views for workspace-role layout selection.

### Routes and authorization

No route files, middleware, policies, Gates, models, requests, migrations, seeders, or database data are in scope. No package files or new dependencies. Expected route-list delta is empty.

## Risks and safeguards

| Risk | Safeguard |
|---|---|
| A layout selector exposes workspace markup to a student, guest, or incapable officer | Set layout only after existing role/tab validation; use capability methods; test page source for those audiences. Keep published tab on public layout. |
| Counts leak private item existence or authors | Scope per role and owner; advisers/superadmins receive permitted counts only; never pass titles/content in their data. Use custom `user_id` keys. |
| Count work duplicates list/dashboard queries | One grouped status aggregate per module/request, reused by shell/dashboard; no per-row queries and no Blade queries. Measure the final queries by inspection; do not add a cache/data table. |
| Drawer, dropdown, bell, or menus fall behind sticky/cards | Define and document one z-index scale; avoid accidental stacking contexts; verify every open state and focus return. |
| Small screens or zoom hide actions | Test 320, 360, 768, 1024, 1440px and 200% zoom; retain 44px targets and a no-JavaScript expanded navigation path. |
| Role changes mid-session leave stale navigation | Recompute capability-filtered navigation from the current authenticated user on every request; do not persist role data in localStorage. |
| Private PDFs or rejected posts become reachable from a new preview | Reuse existing show/file routes and policies; never expose filesystem paths; keep preview queries inside current authorized controller scope. |
| Product notes conflict on colors or font | Use the attached latest brief and active `--ssite-*` tokens (blue/navy); do not update the older green/gold `PRODUCT.md` note in this phase. Use the existing font stack and ask before adding any font asset/network dependency. |
| Adviser roster request exceeds existing role capability | Keep About officer-term manager President-only; Adviser gets existing role/position assignment only unless the user separately authorizes a permission change outside this visual-only plan. |

## Build order after approval

Each batch gets a short file/route/risk plan and waits for the user's “go” before edits. After its bounded implementation and audit, update `docs/WORKSPACE_PROGRESS.md`, report files and evidence, and stop for the next “go”.

1. **Tokens and shell:** new `layouts.workspace`, capability-filtered nav config, top bar, collapse/rail, phone drawer, account dropdown link, accessibility and no-JS behavior. Route diff empty.
2. **President workspace:** dashboard, review queue/preview, officer-term manager; keep existing President checks. Route diff empty.
3. **Posting officers:** Multimedia, Secretary, and Auditor dashboards and role-filtered navigation. Route diff empty.
4. **Adviser and Superadmin:** assignment dashboards and user manager inside the shell; counts-only content scope remains. Route diff empty.
5. **Management views:** module lists and form shell one module at a time; Published stays public, authorized management tabs and create/edit are workspace. Route diff empty for each.
6. **Shared completion:** notifications/profile shell pages, empty states, drawer/dropdown stacking and interaction polish, then docs updates and final verification. Route diff empty.

Final verification after implementation must follow the supplied checklist: role-by-role page/source checks; 320/360/768/1024/1440 widths; 200% zoom; keyboard-only; reduced motion; JavaScript disabled; empty data, long names/titles, missing imagery; private PDF and notification behavior; route snapshot comparison. No test or database command is included without first confirming the configured test DB is not `ssitecms`.

## Optional later

New activity logging, drafts, scheduling, comments, calendar views, added analytics, new permissions, and new tables/columns are outside this workspace scope.

## Approval gate

This document is the complete Phase 2 deliverable. No source code or other documentation has been changed. Implementation begins only after the user approves this plan; each later batch also waits for its own “go”.
