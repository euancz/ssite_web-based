# SSITE Design Review

## Progress log
- 2026-10-14: Reconstructed the state from the branch baseline, git status, and the project files. The review file exists, there is no design-context file (`PRODUCT.md`, `DESIGN.md`, or `.impeccable`), and no approved IDs were found. Frontend-only changes are limited to CSS/Blade files; no backend routes/controllers/models/policies or database logic were changed in the working tree. State read: `STATE B`.
- 2026-10-14: Added a status column and marked all current review findings as `todo` to preserve the unapproved review state without changing any app code.

**Review date:** 2026-10-14  
**Scope:** Frontend source review only. No files other than this report were created or edited. Routes, roles, behavior, authentication, queries, and data were not changed.  
**Approved IDs:** none

## Design context

- **Audience:** Mostly students browsing on phones, student officers creating and managing organization content, and an adviser reviewing submissions and maintaining users and annual officer records.
- **Brand personality:** Student-led, approachable, institutional, and recognizably tied to SSITE and Mabalacat City College. The review preserves the light-blue top bar, navy rounded navigation pill and offset shadow, pixel-style display type, grid backdrop, offset-shadow cards, SSITE logo and wolf imagery, and existing palette as binding cues.
- **Tokens found:** `public/css/app.css:1-13` defines `#8AD5F4` blue, `#C7ECF9` pale blue, `#17324D` navy, `#3E5972` soft navy, and `#3DAFEA` line blue, plus hover timing/scale/shadow variables. Poppins is the global sans (`public/css/app.css:24`, `resources/css/app.css:8-10`); `Universal` is only named as a fallback in the brand label (`public/css/app.css:155`). Spacing is mostly repeated 4/8/12/16/24px values with page-specific exceptions. Radii range from 2-8px, pills, circles, and the About page's alternating 24px corners. Shadows are generally short navy/gray offsets; home stylesheet rules have stronger card shadows.
- **Patterns:** Sticky shared header; role-neutral public pill; role links in avatar dropdown; shared dashboard/page-header, cards, status badges, workflow tabs and overflow-wrapped data tables; details/summary action menus; native dialog confirmations; explicit loading-independent empty states; hover zoom clipped in media frames; reduced-motion and hover-capability CSS; grouped profile forms.
- **Review limits:** No browser automation is available in this session, so the app was not served and no 320/768/1024/1440px, keyboard, zoom, or rendered contrast pass was possible. Authenticated pages were reviewed from Blade/CSS only. Scores below are source-based estimates, not rendered or user-tested results.

## How to read the scores

The UX critique uses Impeccable's ten Nielsen heuristics, each scored 0-4 in this order: **Status, Real-world match, Control, Consistency, Error prevention, Recognition, Efficiency, Minimalism, Recovery, Help.** The compact score string shows all ten values and the total out of 40. Technical audit scores are 0-4 in this order: **Accessibility, Performance, Theming, Responsive, Integrity**, followed by total out of 20. Responsive and performance scores are provisional where rendered behavior is needed.

## Group 1 — Shared layout

**UX critique:** `3 / 3 / 3 / 3 / 3 / 3 / 2 / 2 / 3 / 1 = 26/40`  
**Technical audit:** Accessibility 2, Performance 3, Theming 3, Responsive 3, Integrity 3 — **14/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| S-01 | P1 | `public/css/app.css:186-202` | `.site-search-input:focus` explicitly removes both outline and box shadow without a replacement. Keyboard users can lose the visible position in either search field. | Keyboard users, including students using assistive technology | Restore a high-contrast focus-visible ring using an existing navy/blue token. | `harden` | todo |
| S-02 | P1 | `public/css/app.css:210-224`; `resources/views/layouts/app.blade.php:54-74` | Shared icon buttons have a 36×36px hit box. On phones this is below the 44×44px target and makes search, notifications, and account controls harder to tap. | Students and officers using phones | Increase the hit area to at least 44px while preserving icon scale and header spacing. | `adapt` | todo |
| S-03 | P2 | `resources/views/layouts/app.blade.php:203-210` | The account menu renders a Settings button with no `type`, destination, or behavior in the view. This looks actionable but appears inert. This is recorded as an existing product issue, not a design-only fix. | Signed-in students, officers, and adviser | Confirm intended product behavior before changing this control. | `clarify` (only after product intent is confirmed) | todo |

**Keep this:** role-neutral pill, avatar role links, sticky header, bell and nav-dot distinction, and the existing blue/navy identity.

## Group 2 — Home page

**UX critique:** `3 / 3 / 3 / 3 / 3 / 3 / 2 / 2 / 2 / 1 = 25/40`  
**Technical audit:** Accessibility 2, Performance 2, Theming 2, Responsive 2, Integrity 2 — **10/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| H-01 | P1 | `resources/views/pages/home.blade.php:5-80`; `resources/views/layouts/app.blade.php:9-16`; `public/css/home.css:5-16,137-143` | The home view uses `.home-content`, `.home-heading`, `.featured-article`, and `.secondary-articles`; `home.css` defines a different `.home-page` / `.home-hero` / `.news-grid` structure. The layout links only `css/app.css` and the home view does not push `home.css`, so the page-specific layout rules do not reach this markup. The page can lose its intended layout and home identity. | All visitors, especially phone users scanning featured content | Reconnect the existing home view to its matching styles or align selectors within the current design system; preserve the existing brand cues. | `adapt` | todo |
| H-02 | P1 | `resources/views/pages/home.blade.php:23-26,45-48,67-70` | Each linked image has `alt=""`, and the image-only anchors have no accessible name of their own. A screen reader can encounter an unlabeled link before the separate title link. | Screen-reader users | Give the image link a concise accessible name based on its content, without duplicating visual copy. | `harden` | todo |

**Keep this:** clear “What’s New” heading, bylines/dates, content-specific empty messages, and separate article/activity/achievement destinations (`resources/views/pages/home.blade.php:7-83`).

## Group 3 — About page

**UX critique:** `3 / 4 / 3 / 3 / 3 / 3 / 2 / 3 / 3 / 2 = 29/40`  
**Technical audit:** Accessibility 2, Performance 3, Theming 3, Responsive 3, Integrity 3 — **14/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| A-01 | P1 | `public/css/about.css:8-16,58-64` | The page title is 19px and mission/vision copy is 12px with 1.36 line-height. These are small sizes for primary About content and increase reading effort, especially on phones and at zoom. | Students and community readers, particularly low-vision readers | Raise body copy and page-title sizes modestly within Poppins and the current palette; check wrapping at narrow widths. | `typeset` | todo |

**Keep this:** mission/vision, current-year officer cards, annual Leadership History accordion, adviser-only gear, and the meaningful photo alt text/empty roster message (`resources/views/pages/about.blade.php:14-24,25-80,83-120`).

## Group 4 — Articles, Activities, Achievements

**UX critique:** `3 / 3 / 3 / 4 / 3 / 3 / 3 / 2 / 3 / 2 = 29/40`  
**Technical audit:** Accessibility 3, Performance 3, Theming 3, Responsive 3, Integrity 4 — **16/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| C-01 | P2 | `resources/views/articles/index.blade.php:69-120`; parallel patterns in `activities/index.blade.php:62-121` and `achievements/index.blade.php:62-122` | List cards can show status, title, metadata, per-row action menu, and bulk-selection controls together. The consistent pattern is useful, but secondary actions can compete with the title/read action as the list grows. | Officers and advisers scanning longer lists, especially on phones | Keep the title/read action dominant and group secondary actions under the established `⋮` menu; verify the current visual balance before changing structure. | `polish` | todo |

**Keep this:** shared workflow tabs and status badges, role-specific controls rendered through Blade conditions, empty-state text, confirmation dialogs, clipped hover zoom, and shared row action menus. The bundled detector flagged empty `src` values on the initially hidden local-image preview elements at `articles/_form.blade.php:42`, `activities/_form.blade.php:43`, and `achievements/_form.blade.php:49`; these are populated after file selection and are false positives for the initial hidden state.

## Group 5 — Documents and Liquidation

**UX critique:** `3 / 3 / 3 / 3 / 3 / 3 / 2 / 2 / 3 / 2 = 27/40`  
**Technical audit:** Accessibility 3, Performance 3, Theming 3, Responsive 3, Integrity 4 — **16/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| D-01 | P2 | `resources/views/documents/_form.blade.php:4-13`; `resources/views/liquidation/_form.blade.php:1-20` | These form templates compress multiple field, error, help, and action elements onto single long lines. This makes it harder to maintain clear label/error relationships and increases the chance that future visual adjustments become inconsistent. | Officers and advisers using upload forms; maintainers refining them | Keep the existing fields and behavior, but format only changed markup into the established shared field pattern when this group is approved. | `polish` | todo |

**Keep this:** PDF-only file guidance, current file name and replace instructions, role-aware listing actions, explicit empty states, and the shared scroll-region data-table pattern (`resources/views/documents/index.blade.php:38-66`; `resources/views/components/ui/data-table.blade.php:3-4`).

## Group 6 — Auth and profile

**UX critique:** `3 / 3 / 3 / 3 / 2 / 3 / 2 / 2 / 3 / 2 = 26/40`  
**Technical audit:** Accessibility 2, Performance 3, Theming 3, Responsive 3, Integrity 3 — **14/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| P-01 | P1 | `public/css/app.css:1097-1101` | The current login popover used a 15%-opacity focus outline, making keyboard focus difficult to see. The separate `public/css/login.css` is not referenced by the Blade views. | Keyboard users signing in | Use a solid navy focus ring while preserving the current field and popover design. | `harden` | todo |
| P-02 | P2 | `resources/views/profile/complete.blade.php:6-24`; `resources/views/profile/partials/fields.blade.php:8-125` | The forced completion page introduces a multi-section form but does not state how many sections remain or summarize the required fields before the user begins. The current field grouping is helpful, but the gate can feel like an unexpectedly long task. | First-time student users, especially on phones | Make the required information and expected effort easier to scan without changing requirements or adding workflow steps. | `clarify` | todo |

**Keep this:** email/password and Microsoft sign-in, grouped Personal/Academic/Contact sections, explicit required fields, field errors, profile-picture controls, and the minimal completion header (`resources/views/layouts/app.blade.php:79-143`; `resources/views/profile/partials/fields.blade.php:8-125`).

## Group 7 — Notifications and search

**UX critique:** `3 / 3 / 3 / 4 / 3 / 3 / 3 / 3 / 3 / 2 = 30/40`  
**Technical audit:** Accessibility 3, Performance 3, Theming 3, Responsive 3, Integrity 3 — **15/20**

No findings remain in this group. A terminal display initially made valid Unicode punctuation look corrupted; codepoint inspection confirmed the source characters are correct, so no source edit was needed.

**Keep this:** all/unread filters, mark-all action, per-notice dismiss, status-specific nav dots, result type filters and counts, minimum-query empty state, and the escaped search-highlighting helper contract (`resources/views/notifications/index.blade.php:7-56`; `resources/views/search/index.blade.php:7-50`).

## Group 8 — Adviser and officer surfaces

**UX critique:** `3 / 3 / 3 / 4 / 3 / 3 / 3 / 2 / 3 / 2 = 29/40`  
**Technical audit:** Accessibility 3, Performance 3, Theming 3, Responsive 3, Integrity 4 — **16/20**

| ID | Severity | File and line | Problem | Who it affects | Proposed fix | Impeccable command | Status |
|---|---|---|---|---|---|---|---|
| M-01 | P2 | `resources/views/officer/dashboard.blade.php:69-71` | Disabled “Edit” and “View” buttons look like controls but do not provide an action or explain why they are unavailable. They can cause officers to try controls that cannot work. | Officers reviewing recent posts | If these are intentionally unavailable, use a non-interactive status treatment or explain the unavailable state; confirm product intent before changing behavior. | `clarify` | todo |

**Keep this:** adviser tabs with the “More” menu, review status and reason, officer “Needs attention” recovery path, user table pagination/filtering, year-aware officer manager, and existing role-conditional form controls (`resources/views/adviser/reviews/index.blade.php:7-69`; `resources/views/officer/dashboard.blade.php:18-83`; `resources/views/adviser/users/index.blade.php:8-97`; `resources/views/adviser/officers/index.blade.php:7-36`).

## Keep this across the product

- The light-blue top bar, navy rounded pill and offset shadow, Poppins typography, SSITE logo/wolf imagery, blue/navy palette, grid backdrop, pixel display treatment, and offset-shadow cards.
- Shared role-neutral pill; role links in the avatar dropdown; adviser tabs' “More” menu; `⋮` row actions; adviser-only About controls; sticky header.
- Focus styles already present on account menu items, data tables, dashboard cards, About controls and row menus; reduced-motion rules; hover transforms restricted to fine hover pointers; media frames clip image zoom.
- Shared status, empty-state, data-table, page-header, avatar and dialog patterns; escaped output conventions and current role-specific Blade rendering.
- Explicit empty states in the home, content lists, notifications, dashboard, and adviser tables.

## Issues found (not changed)

- A visible **Settings** button has no destination or action in the account menu (`resources/views/layouts/app.blade.php:203-210`). This appears to be an existing behavior issue; it needs product intent before implementation.
- Officer dashboard Edit/View controls are disabled (`resources/views/officer/dashboard.blade.php:69-71`); the reason is not explained in the UI. This remains unresolved because the intended reason is unknown.
- No separate `resources/views/auth` login view was found. The current login form is a header popover in `resources/views/layouts/app.blade.php:79-143`; `public/css/login.css` is not referenced by the Blade views and remains untouched.
- The project request names a grid background and pixel-style headings, but the inspected CSS sets the body background to white (`public/css/app.css:27-30`), contains no grid-pattern rule, and names `Universal` only as a fallback for brand text (`public/css/app.css:155`); no matching font asset was found under `public`. This is a source-only discrepancy to confirm against the expected live appearance. It is not proposed for change because those identity cues are explicitly protected.
- The home view/style mismatch in H-01 is fixed at source level; rendered impact still needs screenshot verification.
- The apparent punctuation encoding issue was a terminal display artifact, not a source defect.
- Review concerns the code only. Browser checks, real contrast sampling over image backgrounds, 200% zoom, keyboard Escape behavior, and role-by-role visible-control comparison remain unverified. No live database access, seeding, migrations, or data writes were performed.

## Optional P3 ideas

No P3 taste-only changes are proposed. All listed IDs describe usability or implementation evidence and require your approval before any fix is applied.

## Run notes

- Impeccable `context` command ran from the project using the bundled Windows engine; it found an incumbent visual system and no `PRODUCT.md`. This review used the product facts and binding constraints in your request as context and did not create a separate product file, to honor the “only new file” limit.
- Critique used two independent source-only assessment passes, as approved. There was no browser tool, so no local server or visual overlay was started.
- The bundled detector was run per requested view group. It returned no findings for seven groups. For Articles/Activities/Achievements it emitted three `broken-image` warnings on hidden preview elements with an initially empty `src`; these previews receive a selected local file after user input, so they are false positives for initial rendering.
- The executable provides `context` and `detect`; it does not provide standalone `critique` or `audit` CLI commands. The skill's critique heuristics and audit dimensions were applied manually to source evidence. The detector findings are kept separate from those judgments.
- No P3 preference items were applied. The skill's standalone refinement command is not exposed by the bundled executable; the approved edits follow the corresponding command guidance.

## Approval boundary

The source-level fixes approved by the user are recorded below. No behavior or role-control changes were made.
