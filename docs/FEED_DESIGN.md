# SSITE Post Feed Design

**Status:** Approved; all five module batches and final regression verification are complete.  
**Scope:** Articles, Activities, Achievements, Documents, and Liquidation index and show pages.

## Step 1 — Study and audit

### Context and files reviewed

- Read `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, and the existing source review in `docs/DESIGN_REVIEW.md`.
- No `docs/DESIGN_PROGRESS.md`, `PRODUCT.md`, or `DESIGN.md` exists.
- Reviewed the shared application layout and stylesheet, status badge, avatar, page header, workflow tabs, and rejection modal.
- Reviewed the five module index/show views, models, index/show controller logic, policies, relevant feature routes, configuration, user model, and the supplied SQL schema snapshot at `audit/live-schema-before.txt`.
- The worktree was already modified before this task. Existing edits include `public/css/app.css`, the five feature index views, several other views/docs, and an untracked `docs/DESIGN_REVIEW.md`. Those changes were left untouched.

### Real module columns

The following columns are present in the reviewed SQL snapshot. Workflow and ownership columns are listed because the feed must continue to represent their current state safely.

| Module | Available display columns |
| --- | --- |
| Articles | `title`, `content`, `image`, `created_by_user_id`, `approval_status`, `content_status`, `rejection_reason`, `created_at` |
| Activities | `title`, `description`, `activity_date`, `location`, `image`, `created_by_user_id`, `approval_status`, `content_status`, `rejection_reason`, `created_at` |
| Achievements | `title`, `description`, `awardee`, `category`, `achievement_date`, `image`, `created_by_user_id`, `approval_status`, `content_status`, `rejection_reason`, `created_at` |
| Documents | `title`, `description`, `category`, `file_path`, `original_name`, `file_size`, `created_by_user_id`, `approval_status`, `content_status`, `rejection_reason`, `created_at` |
| Liquidation | `title`, `description`, `amount` (`DECIMAL(12,2)`), `report_date`, `file_path`, `original_name`, `file_size`, `created_by_user_id`, `approval_status`, `content_status`, `rejection_reason`, `created_at` |

`file_path` is private implementation metadata and must never be rendered or linked. The controllers store a sanitized `original_name` and byte `file_size`; show only the original label and a human-readable size. `Liquidation::formattedAmount()` already formats the decimal string without float math.

### Authors, avatars, positions, and timestamps

- All five models relate `created_by_user_id` to `users.user_id` through `author()`. Index queries already eager-load `author`; show controllers load `author` and `reviewer`. No author eager-load controller change is currently required.
- `User::avatarUrl()` returns an available picture URL or `null`; `User::initials()` provides the existing initials fallback. `<x-ui.avatar>` wraps that behavior, but a null author currently renders `?`, so the shared card will need a `Former member`/`FM` fallback without exposing additional profile data.
- `users` has `name`, `role`, `officer_position`, and `profile_picture`. The adviser label can use the author's role; an officer position is available as a model attribute. No email, student number, contact number, or other profile fields should enter the post card.
- Eloquent timestamps provide Carbon `created_at`; list queries sort newest-first with `latest('created_at')`. `config/app.php` sets timezone to `UTC`; see Issues found before choosing how to present absolute timestamps.

### Existing list behavior and clutter

All five list controllers keep the existing role-filtered tab allowlist, fall back to Published for unknown/hidden tabs, query ten records per page, retain GET parameters with `withQueryString()`, and order by newest `created_at`. Advisers see Published, Pending Review, Rejected, Archived, and All; officers see Published, My Posts, and their own Archived; students and guests see Published only. Advisers alone receive a pending count. Documents additionally accept `category`; Achievements search already includes its category field but has no separate category-filter parameter. Preserve these contracts and the existing search fields.

| Current page | Current presentation | Feed-specific opportunity |
| --- | --- | --- |
| Articles | Two-column image/content card on wide screens, then a title, author/date row, 180-character excerpt, status, Read link, and separate adviser bulk selection. The index has a separate boxed heading/action, tabs, and filter form. | Make author and relative time the first scan point; use the full-width post image and keep article posts free of a module strip. |
| Activities | Same card pattern; date/location are inline metadata rather than a distinct event band. | Give the event date and location a consistent band below the image. |
| Achievements | Same pattern; awardee, date, and category are mixed into inline metadata. | Highlight the awardee and separate category/date into a predictable module band. |
| Documents | PDF icon/image block and title/meta/excerpt; file actions sit separately from metadata. Category currently filters with the `category` GET parameter. | Use one attachment card for safe filename, file size, category, and authorized View action; guests are sent to login before opening the PDF. |
| Liquidation | PDF block; amount, report date, size, author/date appear together in metadata, with View and a crowded menu. | Group the PDF attachment with its amount and report date, without exposing its storage path. |

Across modules, the page header and search/filter controls take substantial boxed space; cards do not consistently show avatars or relative time; images have empty alt text and no explicit dimensions; status badges are currently rendered even on Published; descriptions are truncated without an in-place expansion; and detail pages split metadata, post body, and management actions into separate areas. Adviser review controls are currently on show pages rather than in Pending Review cards. The adviser-only bulk archive selection and confirmation flow is also present and must not be lost.

`<x-ui.workflow-tabs>` already derives visible tabs from the controller allowlist and preserves search (and Documents category). Its native `details`/`summary` menu has basic Escape/outside-click handling, but lacks explicit `aria-haspopup`, an `aria-expanded` state, and a visible chevron. `<x-ui.status-badge>` already supplies the existing status colors. The shared layout supports `@push('styles')` and `@stack('scripts')`.

### Route baseline

`php artisan route:list --json` completed before any application edits and reported **102 routes**. The complete JSON snapshot is saved outside the repository at:

`C:\Users\lukee\.copilot\session-state\043ac43c-b450-4c3c-9ea6-16b2ed4d136f\files\route-list-before.json`

It is kept outside the worktree so the pre-approval repository change remains limited to this design document. No route file was edited.

### Issues found and tracked

- `config/app.php` uses `UTC`, while the requested absolute post timestamp is intended for Philippine readers. Validate the intended timezone and stored timestamp convention before changing any timezone behavior; no timezone change is proposed here.
- `resources/views/achievements/index.blade.php` previously used the browser title “Activities” although the page heading and route are Achievements; the title was corrected as part of the Achievements batch.
- The source review documents a discrepancy between the requested grid/pixel-style identity and the current source: `body.site-body` is white and `Universal` appears only as a fallback font name. Confirm against the user's intended screenshots; do not invent or replace identity assets during this feed-only batch.
- The source-only audit has no rendered, role-authenticated screenshot or 320–1440px / keyboard / 200%-zoom results. Login-only routes will not be accessed through created accounts.

## Step 2 — Proposed design

### Post card wireframes

**Phone (one column, 320–767px; designed from 360px first)**

```text
┌──────────────────────────────────┐
│ avatar  Author · position   ⋮    │
│         2 hours ago · Articles   │
│         [status when applicable] │
│                                  │
│ Title in SSITE display treatment │
│ Escaped excerpt, up to 3 lines   │
│ See more → show page fallback    │
│                                  │
│ ┌──────────────────────────────┐ │
│ │ 16:9 post image / placeholder│ │
│ └──────────────────────────────┘ │
│ module band (where applicable)   │
│                                  │
│ Read full post     Share         │
│ adviser review / permitted bulk  │
└──────────────────────────────────┘
```

**Desktop (centered 640–720px feed column, 768px and wider)**

```text
             ┌─────────────────────────────────────────────┐
             │ avatar  Author · position/Adviser       ⋮   │
             │         relative time · module · status     │
             │ Title in SSITE display treatment            │
             │ Escaped three-line excerpt / See more       │
             │ ┌─────────────────────────────────────────┐ │
             │ │ Full-width 16:9 media or designed state │ │
             │ └─────────────────────────────────────────┘ │
             │ module-specific detail band                │
             │────────────────────────────────────────────│
             │ Read full post  Share  role actions        │
             └─────────────────────────────────────────────┘
```

The show page uses this same card in a `full` variant: complete escaped text and the same media/module details; omit “Read full post”. Keep the page's single H1 in the slim page header and use a heading for each post title.

### Module variants

| Module | Card body/media | Module strip |
| --- | --- | --- |
| Articles | Escaped `content`, 16:9 image using the existing safe `imageUrl()` fallback. | None. |
| Activities | Escaped `description`, existing image fallback. | `activity_date` and `location`, each optional and labeled. |
| Achievements | Escaped `description`, existing image fallback. | Optional `awardee` highlight, `category` chip, and `achievement_date`. |
| Documents | Escaped optional `description`; no large image. | Shared attachment card with PDF mark, sanitized `original_name`, file size, optional `category`, and the authorized View route. Guests are sent to login before opening the PDF. |
| Liquidation | Escaped optional `description`; no large image. | Shared attachment card plus `report_date` and `formattedAmount()` output. Amount is rendered only for viewers already authorized to see the post. |

For image posts, reserve a 16:9 frame with explicit intrinsic dimensions and `object-fit: cover`; use a branded missing-image state based on the existing image fallback, lazy-load below-the-fold images, and link the media to the existing show route. Keep zoom clipped to the media frame, only on fine hover pointers, mirrored for `:focus-visible`, and disabled for reduced motion.

### Shared component and partial inventory

Use the shared `PostCard` for all modules; keep each module index/show wrapper short and pass its existing model, module key, show route, text field, and variant. Reuse existing UI components where possible.

| Proposed path | Responsibility |
| --- | --- |
| `resources/views/components/posts/feed-frame.blade.php` | Slim title/description, role-gated composer, existing tab component, compact GET search/filter row, flash state, feed slot, and pagination wrapper. |
| `resources/views/components/posts/composer.blade.php` | Reuse the signed-in user's avatar and link to the module's existing create route; render only under the existing create policy. |
| `resources/views/components/posts/card.blade.php` | Shared article structure, author/avatar line, Carbon `<time>`, title, safe excerpt/full text, image frame, module slot, status, menu, and footer. |
| `resources/views/components/posts/attachment.blade.php` | Safe PDF attachment label/size/category and authorized buttons; never render `file_path` or a storage URL. |
| `resources/views/components/posts/strips/activity.blade.php` | Optional activity date/location band. |
| `resources/views/components/posts/strips/achievement.blade.php` | Optional awardee/category/date band. |
| `resources/views/components/posts/strips/liquidation.blade.php` | Report date and formatted peso amount alongside the shared attachment. |
| `resources/views/components/posts/row-menu.blade.php` | Existing policy-gated Edit/Archive/Restore/Delete controls and existing confirmation dialogs; adviser review remains limited to Pending Review. |
| `resources/views/components/posts/empty-state.blade.php` | Shared layout with module/tab/role-specific existing or approved copy. |
| `resources/views/components/ui/workflow-tabs.blade.php` | Preserve controller-provided allowlists and query values; add explicit menu chevron, `aria-haspopup`, synchronized `aria-expanded`, and keyboard behavior. |
| `resources/views/components/ui/avatar.blade.php` | Reuse existing avatar picture/initials behavior and support the explicit Former member fallback in the PostCard. |

Truncated text will use `Str::limit(strip_tags(...))` and Blade-escaped output; expanded text remains escaped and preserves line breaks with CSS. The “See more” link keeps its show-page `href` without JavaScript; JavaScript enhances it in place and updates `aria-expanded`. Share is an ordinary show-page link without JavaScript. On public items, small vanilla JavaScript enhances it with `navigator.share` or Clipboard API and an `aria-live` “Link copied” message. Do not render Share for pending, rejected, or archived items, and never share a PDF URL.

The card menu and footer actions must continue to use `@can`/role guards so unauthorized controls are absent from HTML. Keep the adviser rejection modal and pending-only approve/reject logic. Keep adviser bulk archive selection, confirmation, and CSRF form: move its selection affordance into the shared card/footer where appropriate, but retain the existing endpoint, valid IDs, non-archived eligibility, and confirmation. Rejection reasons remain visible only to the post owner and adviser.

### CSS separation and tokens

Keep the shared SSITE palette, fonts, radii, spacing rhythm, and shadows from `public/css/app.css`; do not add Facebook branding, libraries, or assets. The request to separate styles is planned as one shared feed stylesheet plus a separate module stylesheet for each feature:

- `public/css/post-feed.css` — common frame, card, metadata, media, action bar, accessibility, and responsive rules.
- `public/css/articles.css`
- `public/css/activities.css`
- `public/css/achievements.css`
- `public/css/documents.css`
- `public/css/liquidation.css`

Each module's index and show view will push `post-feed.css` and only its matching module stylesheet through the existing `@stack('styles')`. Keep global tokens and unrelated dashboard/site styles in `app.css`; move or remove only the feed-specific legacy selectors from that file to avoid competing duplicate rules. No inline `<style>` or style attributes will be introduced. `Articles` is the first implementation batch, followed by Activities, Achievements, Documents, and Liquidation.

### Questions requiring user choice

Recommended defaults are stated so the design can proceed without changing scope:

1. **Officer header line:** Show `officer_position` beneath an officer's name when present; label adviser authors “Adviser”; show no extra label for student authors. Choose whether to show officer positions or omit them.
2. **Load more:** Keep the existing ten-item pagination and ordinary page-number links; do not add asynchronous Load more unless explicitly approved.
3. **Quiet desktop side space:** Keep a single centered feed column without sidebars or auxiliary content unless explicitly approved.
4. **Stylesheet split:** Use the shared `post-feed.css` plus the five separate module CSS files above, rather than duplicating all shared rules into each module file.

### Controller changes

**Controllers touched: none planned.** Author relationships are already eager-loaded for each index and show page. `officer_position`, `role`, `created_at`, module display columns, and sanitized PDF display metadata are already available. Any newly discovered need for a controller change must be limited to read-only display data (for example, additional eager loading), must preserve visibility/tab/search/order behavior, and must be reported before implementation; do not change query/search logic.

### Risks and safeguards

- Reuse the existing custom model/user keys and policy checks; owner checks remain based on `created_by_user_id` versus `user_id`.
- Continue to rely on each controller's current role/tab filtering and `publiclyVisible()` behavior. A student/guest must not receive non-public post data or management markup.
- The Documents list may be guest-visible by configuration; guest buttons must lead to the login-protected existing routes. Liquidation remains login-only under the current default configuration.
- PDFs remain on the private local disk and are served only by existing authorized controller routes. A missing PDF keeps its card and gets an unavailable state; never expose its stored path/name.
- Category remains a Documents GET filter. Although Achievements has a category column and search field, its controller has no category filter parameter; do not add one under this scope.
- Long titles, missing authors/images/files, empty tables, reduced motion, no JavaScript, narrow phones, keyboard use, and 200% zoom need explicit checks.
- Card transforms create stacking contexts. Keep feed/list overflow visible and verify row menus, rejection/confirmation dialogs, bell, search suggestions, sticky pill, and header remain above cards.

### Build order after approval

1. Implement Articles index/show with the shared component, shared CSS, and `articles.css`; compare its rendered and source behavior to the before state.
2. Implement Activities, then Achievements, each with its own module strip and stylesheet.
3. Implement Documents, then Liquidation with the private attachment card and guest/login behavior.
4. Update `docs/WHERE_IS_WHAT.md`, `docs/SYSTEM_OVERVIEW.md`, and create/update `docs/DESIGN_PROGRESS.md` for the approved completed batches. Do not change unrelated review findings.
5. Run focused Blade/PHP tests or available validation, compare `php artisan route:list` to the saved before snapshot, and perform the manual role/device checklist below. No migrations, seeders, live database writes, authentication changes, or package installs.

### Verification checklist

- Guest, student, officer, and adviser see only their allowed tabs and controls; composer and management markup are absent for guests/students.
- All five feeds show avatar/fallback, name, role/position per the decision above, relative timestamp with full `datetime` and title text, safe body text, proper media/attachment, and module details.
- Articles have no strip; Activities show date/location; Achievements show awardee/category/date; Documents show safe filename/size/category; Liquidation shows filename/size/report date/decimal amount.
- Published omits approval badges; tabs that can contain non-public content show the relevant approval badge, with Archived preserved where applicable.
- See more expands in place with JavaScript and remains a show-page link without JavaScript. Share enhances only public items and always points to the show page.
- Pending adviser cards retain Approve/Reject and only pending items can be reviewed; rejected owner/adviser cards show the reason.
- Document and Liquidation PDF requests still pass through existing authenticated, authorized routes; missing files produce a friendly state.
- Empty tables and missing authors/images/files render without broken layout. No private data or file path is exposed.
- Keyboard focus is visible; menus support keyboard operation and Escape; reduced motion disables motion.
- Check 320px, 360px, 768px, 1024px, 1440px, and 200% zoom for overflow and usable hit targets; inspect dropdown/modal stacking.
- Confirm current ordering, ten-per-page pagination, GET filters, and all 102 routes are unchanged.

**Optional later (not part of this build):** likes, reactions, comments, counts, asynchronous Load more, and desktop side content.  
**Issues found:** UTC timezone concern, Achievements browser-title mismatch, requested-vs-source grid/display-font discrepancy, and lack of role-authenticated rendered screenshots (details above).
