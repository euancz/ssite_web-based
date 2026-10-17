<!-- Records the source audit and proposed shared system; palette and implementation choices remain unapproved until reviewed. -->
# SSITE Design System Proposal

**Prepared:** 2026-10-17  
**Phase:** Audit and proposal only. No application code or routes changed.  
**Decision gate:** Choose a palette and answer the questions in §10 before any build batch. Each later batch still needs its own “go.”

## 1. Audit scope and evidence

- Ran Impeccable `context` for `resources/views/activities/index.blade.php`. The incumbent visual implementation and `PRODUCT.md` were found; no `DESIGN.md` exists. This is an **Operate** system for management surfaces and **Read** for public content.
- Read the app and workspace layouts, shared UI/feed components, public and management styles, Vite/Tailwind setup, and the existing workspace, design-upgrade, feed, sort/filter, design-review, recommendations, system-overview, and where-is-what records. `docs/DESIGN_DIRECTION.md` is absent.
- The workspace shell is **already built** in `resources/views/layouts/workspace.blade.php`, `resources/views/layouts/partials/workspace.blade.php`, and `public/css/workspace.css`. It is not a future shell. The prior `DESIGN_UPGRADE` work also applied semantic aliases, touch/menu fixes, feed reading width, table handling, and dashboard ordering. These are already planned/applied and should not be redone.
- No `DESIGN_SYSTEM_PROGRESS.md` exists. Existing progress is in `DESIGN_UPGRADE_PROGRESS.md` and `WORKSPACE_PROGRESS.md`.
- Captured `php artisan route:list --json` before this proposal in `%TEMP%\SSITECMS-route-list-before.json`: **113 entries**. A fresh snapshot remained at **113 entries**. No route changed in this documentation-only phase; expected route-list diff is empty.
- Git status was clean at the start. No browser automation tool is available in this session. This audit is source-based; it makes no new rendered, responsive, role-authenticated, keyboard, zoom, JavaScript-off, or reduced-motion claims. Previous browser evidence and its limits remain recorded in `DESIGN_UPGRADE.md`.
- The bundled Impeccable view detector reported three warnings for initially empty image-preview `src` values in the Article, Activity, and Achievement forms. Each preview is populated after local file selection; these match the already-recorded detector false positives in `DESIGN_REVIEW.md`.
- No tests, database commands, cache refreshes, migrations, seeders, package installs, or network requests were run.

## 2. Inventory and current problems

### Existing system inventory

| Measure | Current source inventory | Notes |
|---|---:|---|
| Distinct literal colors | **251** | 609 hex/RGB/HSL literal occurrences across CSS, JS, and Blade; includes alpha variants and near-duplicates. The count is exact-string based, so e.g. `#fff` and `#ffffff` count separately. Excludes Tailwind class names and named CSS colors; no literal color was found in JS or Blade by the focused search. |
| Font families | **2 declared families** | Poppins is the body/UI family. `Universal` is named for display headings but no matching font asset was found; its actual availability is unverified. System fallbacks are Poppins’ declared fallback stack. |
| Font sizes | **79 distinct declarations** | 314 `font-size` declarations across current CSS; many one-off fractional and breakpoint values remain. |
| Font weights | **8 distinct declarations** | 159 declarations; common scale is not yet enforced across pages. |
| Line heights | **21 distinct declarations** | 67 declarations. |
| Letter spacing | **19 distinct declarations** | 32 declarations. |
| Spacing shorthands | **208 distinct values** | 620 margin/padding/gap declarations; compound shorthands count as distinct strings. Existing shared scale already includes 4, 8, 12, 16, 24, and 32px equivalents plus page/layout exceptions. |
| Border radii | **25 distinct values** | 167 declarations; shared aliases cover post card, control, and pill, while page-specific and legacy radii remain. |
| Shadow recipes | **47 distinct values** | 66 declarations; shared offset and hover aliases exist alongside page-specific recipes. |
| Z-index values | **21 distinct values** | 41 declarations; shared layer aliases exist but there are also literal values. Stacking contexts from transforms remain a review risk. |
| Inline styles in Blade | **0 found** | Search for `style=` returned none. No color-setting JavaScript was found. |
| CSS custom properties | **62 declarations** | `public/css/app.css` has semantic aliases, but they do not cover the whole application. |
| Button/status class families | **36 class selectors** | A broad source-name scan; includes non-component names containing “button” or “status.” It is not a count of unique rendered visual variants. Shared `site-button`, `dashboard-button`, `status-badge`, and workspace-specific styles coexist with page-local CTAs and controls. |
| Blade view files | **87** | Includes modules, role dashboards/managers, error views, shared components, and pagination. |

The initial broad color scan also found 254 “colors” because it matched words such as `navy` in class/property names. That number was discarded. The 251 count above uses only color literals (`#…`, `rgb[a](…)`, and `hsl[a](…)`), not keyword guesses. It still does not count utility-class colors; a later enforcement pass needs an explicit Tailwind class scan.

### Color source map and near-duplicates

The base token file is `public/css/app.css`. Current named values include `--ssite-blue: #8AD5F4`, `--ssite-blue-light: #C7ECF9`, `--ssite-navy: #17324D`, `--ssite-navy-soft: #3E5972`, `--ssite-line: #3DAFEA`, and danger `#b42318`; semantic aliases were added in the completed T-03 batch. Repeated literal families include white (`#fff`/`#ffffff`), pale blue-gray surfaces (`#effaff`, `#eff4f7`, `#f4f8fa`, `#f7fafb`, `#f8fcfe`), borders (`#dce8ee`, `#dce7ec`, `#e2ebef`, `#e1eaf0`, `#c9dce6`, `#cfe6ee`), and navy-tinted shadows at many alpha levels. Near-duplicates occur across `public/css/app.css`, `workspace.css`, `post-feed.css`, `about.css`, `home.css`, and module CSS. A complete per-literal frequency/export is intentionally not embedded in this proposal; the count and highest-frequency examples above establish the drift and the staged migration will need the machine-readable frequency list before replacement.

The Tailwind Vite plugin is already configured (`vite.config.js`, `resources/css/app.css`). The live Blade layouts load the established `public/css/*.css` assets directly. The proposal keeps this setup and adds no dependency; its token ownership and the distinction between the compiled Tailwind entry and currently linked styles must be preserved during migration.

### Typography, spacing, components, vocabulary

- **Typography:** Poppins is repeated directly in CSS and is the current readable family. `Universal` is an unverified display-family declaration. Sizes range from small 10–12px meta/eyebrows through 30px headings and responsive clamps; prior `DESIGN_REVIEW.md` identified 12px About copy and a 19px title. Pixel-style heading usage is not present in the inspected CSS; this is an identity discrepancy, not permission to add a font or asset.
- **Spacing/radius/shadow:** Existing 4px-based aliases and offset shadows are valuable foundations. They coexist with over 200 spacing values, 25 radii, and 47 shadows. About’s asymmetric 24px corners and page-specific stronger shadows are exceptions requiring an explicit keep-or-map decision.
- **Buttons:** Shared site/workspace buttons, secondary/small/text/icon buttons, module CTA buttons, login/Microsoft controls, and row actions are spread across shared and page CSS. Hover, focus, disabled, and loading treatments are not demonstrably uniform. Keep the public pill identical for every role.
- **Badges:** Shared `status-badge` has approved, archived, neutral, pending, and rejected variants; workspace also has separate status styles. Notification dots and search-type badges are separate information types and should not be collapsed into approval badges.
- **Other components:** Shared UI exists for page headers, stat cards, data tables, avatars, reject modal, workflow tabs, feed cards, sort rows, row menus, empty states, and confirmation actions. Their presence is already applied; consolidate their visual recipes and states without changing markup semantics or capability conditions. Forms, flash messages, breadcrumbs, tooltips, drawer, and menus are not yet one documented implementation across all page groups.
- **Vocabulary:** Source and plans use “pending,” “Needs review,” “approved,” “published,” “active,” “archived,” “rejected,” “Needs attention,” “My submissions,” “Manage,” and “View.” “Published” describes approved+active visibility, while “approved” is a review status; do not treat them as synonyms. Role and position wording varies between “officer,” position names, “adviser,” and “superadmin.” Confirm one glossary in §7 before a global copy pass.

### Artwork sampling

I sampled opaque pixels from the actual PNG assets using a 16-step RGB quantization (about 50,000 samples per large image). The current `SSITE LOGO.png` and `SSITE (NEW LOGO).png` are dominated by quantized light blue **#90D0F0** and deep blue **#105080**, with dark blue shades around **#002030 / #103040**. `Wolf.png` is dominated by blue shades around **#307090**, **#105070**, and **#106080**. The alternate `ssite - revamp logo 2.png` contains teal shades around **#009090** and **#008080**. These are sampled source colors, not proposed CSS values; quantization rounds each RGB channel down to its nearest 16. The blue options below derive from the current logo family; the teal option derives from the alternate SSITE mark. No welcome artwork was found in `public/images`.

### Contrast audit: current state

The prior source review computed `#3DAFEA` on white at **2.47:1**, which fails 4.5:1 for text. Existing documentation says this value is currently used for rules/borders rather than body text; those UI boundaries still need the 3:1 component test against each actual adjacent surface. The current app contains 251 literal colors and several opacity-based shadows, gradients, images, and component states, so a complete rendered foreground/background pair census is not available from source-only inspection. No claim of zero current contrast failures is made. Focus contrast, placeholder contrast, status pairs, disabled controls, light-blue header text, dark pill text, and workspace sidebar pairs remain to be verified on actual rendered states before a palette migration can be considered complete.

### Existing critique and audit scores

These are inherited from `docs/DESIGN_UPGRADE.md` (2026-10-16), which reconciled the earlier `DESIGN_REVIEW.md` scores. They are source-and-limited-guest-browser estimates, not scores newly gathered in this audit. Each score group is UX total / technical audit total.

| Page group | Before UX | Before audit | Notes |
|---|---:|---:|---|
| Shared layout and navigation | 26/40 | 14/20 | Includes focus and touch-target concerns already addressed in T-01. |
| Home | 26/40 | 13/20 | Source updated for home styles and descriptive image links; browser scope in prior report. |
| About and officer history | 29/40 | 14/20 | Small copy and title sizes recorded for later type-system pass. |
| Articles, Activities, Achievements | 29/40 | 16/20 | Shared feed patterns already applied; detector preview warnings are false positives. |
| Documents and Liquidation | 27/40 | 16/20 | Private-document behavior is outside visual scope. |
| Login and profile | 26/40 | 14/20 | No login-only screenshots or authenticated browser review. |
| Notifications and global search | 30/40 | 15/20 | Source-based for authenticated content. |
| Dashboards, managers, and review queue | 29/40 | 16/20 | Source-based; roles were not authenticated in browser. |

No after scores exist for this proposal. Baseline variants are best expressed as **at least 36 button/status-related selector families from the broad scan**, with the exact rendered distinct button variant count unresolved, and **five shared status-badge variants plus separate workspace styles**. Before/after metrics will be re-counted with a defined component selector inventory at final verification; the final audit must report zero raw colors outside the token source and zero contrast failures or list justified exceptions.

### Source-based critique by page group

The priority findings below are from the prior code review and current static scan, not fresh visual browser review. Previous fixes are marked as already applied.

| Group | Critique | Technical audit | Remaining concern / status |
|---|---:|---:|---|
| Public shell/navigation | 26/40 | 14/20 | T-01 already synchronized menu state and touch height; search focus ring still recorded as an issue in `DESIGN_REVIEW.md`, verify current CSS before implementation. |
| Home | 26/40 | 13/20 | Home stylesheet wiring and image-link naming were fixed at source; verify rendered snapshots later. |
| About | 29/40 | 14/20 | 12px mission/vision copy and small title need type review; no current pixel title/grid evidence. |
| Public content feeds | 29/40 | 16/20 | Cards, tabs, menus, and empty states already planned/applied; status badge contrast and states remain unmeasured. |
| Documents/Liquidation | 27/40 | 16/20 | Upload templates have dense single-line markup; do not alter field logic or private PDF handling. |
| Auth/profile | 26/40 | 14/20 | Needs source/screenshot check; login popover is in the shared layout, not a separate auth page. |
| Notifications/search | 30/40 | 15/20 | Existing hierarchy is comparatively consistent; no new issues found in inherited report. |
| Workspace/dashboard/review | 29/40 | 16/20 | Built shell and role dashboards are distinct; contrast/token alignment across sidebar, top bar, cards, and tables is not fully proven. |

## 3. Proposed color system

Use mostly neutrals, then brand, then a small accent (**60/30/10**). The accent is a highlight for active markers, selected tab edge, and small illustration details. It never fills every card, every button, body copy, destructive action, status badge, or the whole workspace sidebar. Status is semantic and independent from the accent. The public pill remains exactly the same for every role.

Both options retain the observed SSITE blue identity and provide AA text shades for saturated brand surfaces. The original `#3DAFEA` remains a decorative line/border candidate only; it is not promoted to text. Values that alter current brand usage require your choice first.

### Option A — Refined (recommended)

Quiet deep-blue primary with a restrained, accessible blue accent sampled from the logo family.

| Role | Proposed value | Usage |
|---|---|---|
| Brand primary | `#17324D` | Primary button, dark pill, active sidebar treatment |
| Primary hover | `#244B69` | Hover-capable devices only |
| Primary pressed | `#102A3E` | Pressed state |
| Brand bar | `#8AD5F4` | Light-blue top bar, current brand surface |
| Brand surface | `#C7ECF9` | Banners and selected soft surfaces |
| Accent | `#0B6B8E` | Small active marker, rare emphasis; proposed accessible blue shade, approval required |
| Accent hover / pressed | `#095A78` / `#074A63` | Accent interactions only |
| Background / surface / raised | `#F7FAFC` / `#FFFFFF` / `#FFFFFF` | Page, cards, dialogs |
| Border / strong border | `#778899` / `#52697C` | Controls, dividers, and strong boundaries |
| Text / muted / on-primary | `#17324D` / `#52697C` / `#FFFFFF` | Main, secondary, and dark-action text |
| Success bg / border / text | `#E7F4EA` / `#527B5D` / `#17613A` | Status triplet |
| Warning bg / border / text | `#FFF3CD` / `#8A6500` / `#714C00` | Status triplet |
| Danger bg / border / text | `#FDEAEA` / `#A33A36` / `#9B1C1C` | Status triplet |
| Info bg / border / text | `#E7F3F8` / `#4E8297` / `#155B7A` | Status triplet |
| Archived/neutral bg / border / text | `#EEF1F3` / `#687985` / `#455563` | Status triplet |
| Focus | `#0B6B8E` | 2px visible outer ring with offset against component surface |

### Option B — Punchy

Same neutral, primary, and SSITE blue foundation; use the verified teal from the alternate SSITE logo for a more distinctive rare accent.

| Role | Proposed value | Usage |
|---|---|---|
| Brand primary / hover / pressed | `#17324D` / `#244B69` / `#102A3E` | Same deep-blue actions and pill |
| Brand bar / surface | `#8AD5F4` / `#C7ECF9` | Same SSITE blue surfaces |
| Accent | `#006B68` | Rare active marker and selected-state emphasis; deep shade derived from sampled `#008080` / `#009090` alternate mark |
| Accent hover / pressed | `#005954` / `#004A47` | Accent interactions only |
| Background / surface / raised | `#F7FAFC` / `#FFFFFF` / `#FFFFFF` | Page, cards, dialogs |
| Border / strong border | `#778899` / `#52697C` | Controls, dividers, and strong boundaries |
| Text / muted / on-primary | `#17324D` / `#52697C` / `#FFFFFF` | Main, secondary, and dark-action text |
| Status triplets and focus | Same values as Option A | Keeps meaning stable across surfaces |

### Contrast checks for proposed shared pairings

Ratios below are calculated from the specified sRGB hex values. Normal text target is 4.5:1, large text 3:1, and component boundaries/focus at least 3:1. Status text pairs all exceed 4.5:1. Each status border is intended to be checked against both its status background and adjacent white surface in the build phase; proposed borders are darker than their status fills. Avoid placing text over artwork or grid lines without checking the actual composite.

| Foreground / state | Surface | Ratio | Result |
|---|---|---:|---|
| `#17324D` body/primary | White | 13.13:1 | Pass |
| `#244B69` hover text | White | 9.19:1 | Pass |
| `#102A3E` pressed text | White | 14.76:1 | Pass |
| White button text | `#17324D` primary | 13.13:1 | Pass |
| White button text | `#244B69` hover | 9.19:1 | Pass |
| White button text | `#102A3E` pressed | 14.76:1 | Pass |
| White text | `#0B6B8E` accent A | 5.99:1 | Pass |
| White text | `#095A78` accent A hover | 7.65:1 | Pass |
| White text | `#074A63` accent A pressed | 9.68:1 | Pass |
| White text | `#006B68` accent B | 6.36:1 | Pass |
| White text | `#005954` accent B hover | 8.21:1 | Pass |
| White text | `#004A47` accent B pressed | 10.14:1 | Pass |
| `#17324D` on SSITE bar | `#8AD5F4` | 8.07:1 | Pass |
| `#17324D` on SSITE pale blue | `#C7ECF9` | 10.50:1 | Pass |
| `#52697C` muted text | White | 5.72:1 | Pass |
| `#17613A` success text | `#E7F4EA` | 6.60:1 | Pass |
| `#714C00` warning text | `#FFF3CD` | 6.93:1 | Pass |
| `#9B1C1C` danger text | `#FDEAEA` | 7.04:1 | Pass |
| `#155B7A` info text | `#E7F3F8` | 6.61:1 | Pass |
| `#455563` archived text | `#EEF1F3` | 6.77:1 | Pass |
| `#778899` control border | White | 3.64:1 | Pass for boundary |

These figures cover the proposed core combinations, not every component state or the current 251-value implementation. Disabled text needs its own readable neutral treatment; opacity reduction must not be used to evade contrast. The focus ring must be tested as a visible boundary on white, light-blue, navy, and status surfaces. If the currently branded `#3DAFEA` is proposed for text or fails any required role in the final census, stop and ask before replacing it with an accessible same-hue value.

## 4. Token specification

Implement after palette approval in the existing CSS source, with semantic custom properties and aliases only. Preserve established aliases as a migration bridge; do not leave two independent authorities once the full migration is complete.

| Token family | Proposed scale/rules |
|---|---|
| Color | Semantic roles listed in §3; explicit status background/border/text triplets; separate brand, accent, neutral, and focus. |
| Font families | Readable Poppins stack for all UI/body. Pixel display family only for H1, brand block, and large hero numbers after verifying/installing no new asset; minimum 28px. `Universal` availability must be confirmed from supplied assets before use. No pixel font for body, labels, controls, tables, or forms. |
| Type sizes | 12, 14, 16, 18, 20, 24, 28, 32, 40px, with fluid H1 clamp around 28–40px. Mobile form inputs at least 16px; other mobile text at least 14px. |
| Weights | 400 body, 500 supporting, 600 labels/eyebrows, 700 emphasis, 800 H1/numbers; remove arbitrary values only where visually safe. |
| Line height | 1.25 display, 1.4 headings/controls, 1.5 body, 1.6 long reading. |
| Letter spacing | Body 0; small eyebrow 0.06em; avoid tracked all-caps copy. |
| Spacing | 4, 8, 12, 16, 24, 32, 48, 64px; page gutter `clamp(12px, 2vw, 24px)`. Existing layout widths and 68ch reading measure stay. |
| Radii | 4px control, 12px card/panel, pill 999px; circle for avatars only. Preserve About corner motif only if explicitly accepted as a page-specific brand exception. |
| Borders | 1px standard, 2px strong/focus; semantic border tokens. |
| Shadows | Two/three elevations using a consistent navy-tinted offset recipe: flat divider, 4px offset card, 8px floating menu/modal. Keep the established offset-card identity; no arbitrary gray shadow colors. |
| Z-index | Grid 0, content 1, sticky pill/header 20, sidebar 25, dropdown 40, drawer 50, modal backdrop 60, modal 70, toast 80, tooltip 90. Map to actual stacking contexts before migration; transformed cards must not trap menus. |
| Motion | 120ms control, 180ms menu, 220ms card; `ease-out`; transform/opacity only; hover effects inside hover-capable media query; disable nonessential motion under `prefers-reduced-motion`. |
| Focus | 2px accent ring plus 2px offset; maintain visible focus on all surfaces and never remove it without replacement. |

**60/30/10 rule:** about 60% neutral page/surface, 30% SSITE brand blue/navy and typography, up to 10% accent/status emphasis combined. Status color is reserved for status, not decoration.

## 5. Label and component system

| Label type | Proposed one style |
|---|---|
| H1 page title | Only pixel display use; 28–40px, 700+, line-height 1.15, text balance; ensure fallback is readable. |
| H2/H3 | Poppins 20/18px, 600–700, sentence case. |
| Eyebrow | Poppins 12px minimum, 600, restrained 0.06em tracking; uppercase only for short section labels. |
| Form label | Always visible above control, 14–16px/600; sentence case; explicit “Required” or “Optional” marker; help below; error with icon and associated text. |
| Button | 14–16px/600, verb-first, sentence case, shared height/padding; one primary action per view. |
| Badge/chip | 12px/600, fixed compact height, text always present, icon where it clarifies. |
| Navigation | 14px/600, icon paired with text; active state has fill/marker/weight and `aria-current`; icon-only control has accessible name and hover/focus tooltip. |
| Table header/data | 12–14px/600 header, 14px data; clear alignment and contrast. |
| Meta | 14px, muted token with AA contrast; dates/authors/counts use this role. |
| Numbers | 24–40px/700, tabular numerals. |

Shared component contract: one implementation and named variants, with default, hover, focus-visible, active, disabled, loading, and error states where applicable. All role-dependent controls remain omitted from HTML by current Blade authorization/capability conditions.

| Component | Variants/states to normalize | Existing implementation |
|---|---|---|
| Buttons/icon buttons | Primary, secondary, quiet/text, danger, icon; hover/focus/pressed/disabled/loading | `components/ui` and `public/css/app.css`, `workspace.css`; site/page-local buttons remain duplicates to map |
| Badges | Pending review, approved, rejected, archived, neutral/info; icon+label | `components/ui/status-badge`, workspace status styles |
| Cards | Public feed, detail, dashboard stat, panel, empty | `components/posts`, `components/workspace/posts`, `components/ui/stat-card` |
| Tabs | Public module, workflow; selected with marker and `aria-current` | `components/ui/workflow-tabs`, `components/workspace/ui/workflow-tabs` |
| Menus | Account dropdown, row menu, overflow, workspace nav | layouts, `components/posts/row-menu`, workspace partials |
| Modals/dialogs | Confirm, reject; focus, Escape, cancel, destructive emphasis | `components/posts/confirm-action`, `components/ui/reject-modal` |
| Flash messages | Success, info, warning, danger; icon, live-region role, dismiss if already supported | Existing layout/session message markup; style mapping needed |
| Fields | Text, select, checkbox, file, textarea; help/error/disabled/focus | Five module `_form` views, profile partials, adviser forms |
| Tables/rows | Standard, responsive horizontal scroll, empty; row status/actions | `components/ui/data-table`, adviser managers, dashboards |
| Stat cards | Neutral and semantic counts; tabular number and caption | `components/ui/stat-card`, dashboard markup |
| Empty states | Standard, filtered, review queue; short explanation and allowed next action | `components/posts/empty-state`, workspace equivalent |
| Page headers | Public and workspace; title, description, one primary action | `components/ui/page-header` and page-specific headers |
| Breadcrumbs | One compact style if present; separator and current-page label | Existing use is inconsistent/limited; inventory before creating a shared component |
| Avatars/tooltips | Image/fallback initials; accessible name; tooltip on icon-only focus/hover | `components/ui/avatar`; tooltip treatment not unified |

## 6. Workspace rules

The workspace shell already exists and follows the same codebase. Keep it denser and calmer than public feeds, while using the same semantic tokens, type roles, buttons, labels, badges, focus rings, and glossary.

- Sidebar: dark blue from the public pill (default proposed); active item uses a visible bar or fill plus weight/icon and `aria-current`. Preserve the role-specific sidebar/capability gates already specified in `WORKSPACE_PLAN.md`.
- Top bar: use the SSITE light-blue token, dark navy text, shared notification/account controls, and the same z-index model as the sticky public pill.
- Dashboard: prioritize each role’s next allowed action, retain existing stats and role branches; use tabular numbers and shared stat cards.
- Review queue: text+icon status, title/author/date scan order, clear pending review label; keep approve/reject controls and reason flow exactly as authorized now.
- Lists: shared table/list components, clear status and ownership labels, mobile scroll contained within the table wrapper, empty state in shared style.
- Forms: visible labels/help/errors and shared field/button treatment; no validation, action, file, ownership, or upload changes.
- Security and routing: preserve existing URL/name/middleware, authorization, `user_id` ownership, custom keys, escaped output, CSRF/method directives, visibility rules, and private PDF controller delivery. Do not expose workspace markup to guests, students, or capability-less officers.

## 7. Proposed glossary

These are proposed display strings. They do not change stored statuses, roles, positions, routes, or notification behavior. Search notification/email copy before adoption; none is rewritten in this phase.

| Concept | Canonical display wording | Existing variants to reconcile |
|---|---|---|
| Roles | Student; Officer; Adviser; Superadmin | “super admin”/“superadmin”; capitalization differences |
| Position | Use configured position title exactly | “Multimedia,” “Secretary,” “Auditor,” “President”; never substitute role for position |
| Review states | Pending review; Approved; Rejected | “Pending,” “Needs review,” “Needs Review” (use “Needs review” only as a queue heading) |
| Content state | Active; Archived | “Published” is a visibility/outcome phrase, not a stored approval state |
| Public visibility | Published | Means approved and active together; do not badge it as a fourth review state |
| Create/submit | Create [item]; Submit for review | “Add,” “New,” “Submit” where same action |
| Review actions | Approve; Reject | Do not rename approval/rejection actions differently per module |
| Content actions | Archive; Restore; Delete permanently; Download; View | “Delete,” “Remove,” “Open” where semantically identical; private PDF still downloads through authorized route |
| Navigation | Dashboard; Review queue; My submissions; View site; Notifications; Profile | “Needs review,” “Manage posts,” “Manage About Us” where they point to the same destination |
| Empty states | “No [items] yet.” / “No [items] match these filters.” | Use separate unfiltered and filtered messages; preserve existing meaningful contextual detail |

Review existing messages in views/controllers/notification templates before accepting this glossary; preserving behavior means notification text changes wait for the relevant approved copy batch.

## 8. Migration plan

Every batch after approval has its own chat plan and explicit “go” gate. `route:list` diff must stay empty throughout.

| Stage | Work | Files/batches | Main risks and checks |
|---|---|---|---|
| 1.1 | Expand existing aliases into complete semantic token source with **no visible change** | `public/css/app.css`; document/progress | Cascade order, `public/css` direct linking, Tailwind’s separate Vite entry, computed-style parity |
| 1.2 | Map shared layouts/components to tokens | `layouts/*.blade.php`, `components/**`, shared CSS, `layouts/partials/**` | Role markup must remain capability-gated; sticky pill, drawers, dropdown/modal stacking |
| 1.3 | Map module styles and public views | public module CSS/views and `post-feed.css` | Near-duplicate collapse only when computed result is equivalent; private PDFs unchanged |
| 1.4 | Map workspace, role manager, auth/profile/notification/search/error styles | `workspace.css`, role views, corresponding CSS/views | Sidebar/capability markup, existing custom roles, standalone error layouts |
| 1.5 | Remove residual inline/raw/JS-set colors and verify no visual change | whole CSS/Blade/JS inventory | Tailwind classes, native controls, third-party/pagination markup; no JS color setters currently found |
| 2.1 | Apply approved palette, type and label tokens | token file only where possible; progress log | Any brand-color contrast failure pauses for approval; pixel font asset is absent/unverified |
| 2.2 | Normalize buttons, badges, flashes, fields and errors | shared components and linked CSS | Keep every role’s control set and all form semantics/actions unchanged |
| 2.3 | Normalize public pill, workspace nav/top bar, tabs, breadcrumbs and menus | shared layouts/partials/styles | Stacking contexts, sticky positioning, icon labels and active shape |
| 2.4 | Normalize cards, post cards, stat cards, tables, empty states | shared UI/feed components and CSS | Offset shadow identity; table mobile scroll; no content/query changes |
| 2.5 | Apply to workspace dashboards, queue, forms | workspace and role views/styles | Capability-level markup check for each role; no controller edits expected |
| 2.6 | Apply to public, auth/profile, notifications/search and error pages | page views and CSS | Login-only review uses supplied screenshots; 16px phone form fields |
| 2.7 | Motion, final polish, glossary copy pass | shared CSS and views; any notification copy separately listed | Reduced motion, hover devices, action wording; no email copy change without locating and listing exact files |

Risks: sticky ancestors with overflow; transforms that create stacking contexts; framework utility colors/cascade; inline styles (none found now); image artwork behind text; generated Laravel pagination/native controls; CSS loaded by Blade directly while Tailwind is configured through Vite; role conditions; third-party markup. Expected route-list diff: **empty**. Controllers touched: **none planned**; if display-only data is proven necessary, pause and seek approval before touching a controller.

### Verification gates after an approved batch

Use before/after screenshots when a browser is available; otherwise record source evidence and limitations. Check 320, 360, 768, 1024, and 1440px; keyboard-only focus; reduced motion; JavaScript off; empty states; long titles/names; missing images; increased text/forced contrast; and 200% zoom. Confirm route-list equality, Blade compilation/build only after approval, role-by-role controls and no management markup for guest/student/capability-less officer. Contrast-audit every touched rendered pair. Do not run tests or DB-refresh commands until the configured test DB is demonstrated not to be `ssitecms`.

## 9. Questions for approval

1. Palette: **Option A Refined (recommended)** or **Option B Punchy**? Default: A.
2. May the accent appear on the single primary action, or should it stay on highlights/active markers? Default: highlights only; primary buttons remain deep blue.
3. Adopt tabular figures for counts and stat numbers? Default: yes.
4. Use the public pill’s dark blue for the workspace sidebar? Default: yes.
5. Keep uppercase eyebrow labels, minimum 12px and sparse? Default: yes.
6. Keep the subtle grid in workspace content? Default: yes, but the current source review did not find a grid rule; confirm the intended reference before adding it.
7. Approve a markdown-only `docs/STYLEGUIDE.md` after the build? Default: yes; it is not created in this phase.
8. Add a small raw-color check after tokenization? Default: no. If yes, approve the check design and scan scope before adding it; no test/dependency is assumed.

## 10. Optional later

- **Dark mode:** token-layer variant and contrast review for all states; moderate effort and separate approval.
- **Printable report style:** print-only tokens/layout for dashboards and financial reports; low-to-moderate effort, no new route.
- **Email styling:** shared copy/status style only after identifying notification mail templates; no email content changed in this phase.
- **New routes/tables:** not proposed. Any new route/table requires separate approval and is outside this visual system.

## Audit notes and unresolved items

- Current CSS lacks evidence for the requested pixel headings and subtle grid; `DESIGN_REVIEW.md` notes the same mismatch. The existing plan protects them as brand cues, but they need a screenshot/reference before a safe visual implementation.
- Search focus treatment, About small text, status-pair contrast, workspace sidebar contrast, disabled states, and 200% zoom need later verification. Existing `DESIGN_REVIEW.md` issues about inert Settings and disabled officer dashboard controls are product behavior findings; they remain unmodified and outside this visual proposal.
- Palette ratios are calculated proposals, not an audit of every current rendered pair. Do not announce zero contrast failures until the complete component/state census is measured.
- “Controllers touched”: none. “Shared files touched”: none in this phase. Only this proposal document was created.


## Appendix A � Literal color frequency and file locations

The following list covers every hex/RGB/HSL literal found by the audit scan. Counts are literal occurrences; locations list each file containing the value. Named CSS colors and Tailwind utility classes are excluded and must be included in the enforcement scan before a raw-color-zero claim.

| Literal | Uses | Files |
|---|---:|---|
| `#000` | 1 | public/css/app.css |
| `#00a4ef` | 1 | public/css/app.css |
| `#0565ad` | 1 | public/css/home.css |
| `#07536f` | 1 | public/css/app.css |
| `#0877a7` | 3 | public/css/app.css |
| `#123b4a` | 1 | public/css/login.css |
| `#12546c` | 1 | public/css/login.css |
| `#146f98` | 6 | public/css/workspace.css |
| `#147da9` | 1 | public/css/workspace.css |
| `#167da8` | 1 | public/css/workspace.css |
| `#1688bd` | 2 | public/css/workspace.css |
| `#17324d` | 24 | public/css/about.css, public/css/app.css, public/css/workspace.css |
| `#173b55` | 2 | public/css/liquidation.css |
| `#17633b` | 2 | public/css/app.css |
| `#176783` | 2 | public/css/login.css |
| `#176f98` | 1 | public/css/workspace.css |
| `#203f53` | 1 | public/css/liquidation.css |
| `#23643a` | 2 | public/css/app.css |
| `#243f52` | 2 | public/css/app.css |
| `#244b69` | 2 | public/css/workspace.css |
| `#244f73` | 1 | public/css/errors.css |
| `#24536e` | 5 | public/css/app.css, public/css/liquidation.css |
| `#25394b` | 1 | public/css/about.css |
| `#263b4e` | 1 | public/css/about.css |
| `#294b64` | 2 | public/css/workspace.css |
| `#334155` | 2 | public/css/app.css |
| `#334b60` | 1 | public/css/app.css |
| `#344b60` | 1 | public/css/about.css |
| `#36596d` | 1 | public/css/liquidation.css |
| `#385c75` | 1 | public/css/workspace.css |
| `#39577a` | 1 | public/css/app.css |
| `#3dafea` | 3 | public/css/app.css, public/css/workspace.css |
| `#3e5972` | 4 | public/css/app.css |
| `#3f3f3f` | 1 | public/css/app.css |
| `#475569` | 1 | public/css/app.css |
| `#506a7e` | 1 | public/css/workspace.css |
| `#517f96` | 1 | public/css/liquidation.css |
| `#52697c` | 2 | public/css/app.css |
| `#526b7d` | 1 | public/css/about.css |
| `#536d7c` | 2 | public/css/app.css |
| `#537991` | 1 | public/css/workspace.css |
| `#555555` | 1 | public/css/home.css |
| `#607487` | 2 | public/css/app.css |
| `#60798b` | 1 | public/css/workspace.css |
| `#64748b` | 1 | public/css/app.css |
| `#64c5df` | 1 | public/css/about.css |
| `#666666` | 2 | public/css/home.css |
| `#668293` | 1 | public/css/liquidation.css |
| `#728694` | 2 | public/css/app.css, public/css/post-feed.css |
| `#765700` | 2 | public/css/app.css |
| `#777` | 3 | public/css/login.css |
| `#7894a7` | 2 | public/css/post-feed.css |
| `#79bbd3` | 1 | public/css/workspace.css |
| `#7dd3ef` | 1 | public/css/about.css |
| `#7f1d1d` | 1 | public/css/post-feed.css |
| `#7fba00` | 1 | public/css/app.css |
| `#842a24` | 1 | public/css/app.css |
| `#85a2b4` | 1 | public/css/workspace.css |
| `#8ad5f4` | 1 | public/css/app.css |
| `#8eb5c8` | 1 | public/css/liquidation.css |
| `#94a3b8` | 2 | public/css/app.css |
| `#999` | 1 | public/css/login.css |
| `#9b342d` | 1 | public/css/app.css |
| `#9bb5c6` | 1 | public/css/workspace.css |
| `#9bcfe0` | 1 | public/css/workspace.css |
| `#9c3030` | 3 | public/css/app.css |
| `#9c3f36` | 1 | public/css/liquidation.css |
| `#9cb5c5` | 3 | public/css/documents.css, public/css/post-feed.css |
| `#9ccfe2` | 2 | public/css/workspace.css |
| `#9ed6b2` | 2 | public/css/app.css |
| `#a12b21` | 1 | public/css/app.css |
| `#a43a3a` | 1 | public/css/app.css |
| `#a9cbdc` | 1 | public/css/workspace.css |
| `#a9d9ec` | 1 | public/css/workspace.css |
| `#abdced` | 1 | public/css/workspace.css |
| `#b42318` | 6 | public/css/app.css, public/css/login.css, public/css/workspace.css |
| `#b6dcec` | 2 | public/css/workspace.css |
| `#b7cbd6` | 2 | public/css/app.css, public/css/post-feed.css |
| `#b7e5f6` | 1 | public/css/workspace.css |
| `#b8b8b8` | 1 | public/css/login.css |
| `#b8dce8` | 8 | public/css/app.css |
| `#b8e7f3` | 2 | public/css/app.css |
| `#b9cbd7` | 4 | public/css/app.css |
| `#b9cfdb` | 1 | public/css/workspace.css |
| `#b9d7e5` | 4 | public/css/workspace.css |
| `#b9d9e8` | 1 | public/css/app.css |
| `#b9dce9` | 1 | public/css/workspace.css |
| `#bb7d13` | 1 | public/css/workspace.css |
| `#bfd5e0` | 1 | public/css/workspace.css |
| `#bfd6ea` | 1 | public/css/app.css |
| `#bfdfe8` | 1 | public/css/app.css |
| `#bfe9f4` | 1 | public/css/app.css |
| `#c4d6df` | 1 | public/css/liquidation.css |
| `#c4d7e1` | 1 | public/css/workspace.css |
| `#c5dbe6` | 1 | public/css/workspace.css |
| `#c5e5f2` | 1 | public/css/workspace.css |
| `#c5e7f4` | 1 | public/css/workspace.css |
| `#c7ecf9` | 1 | public/css/app.css |
| `#c8e6f2` | 2 | public/css/documents.css, public/css/post-feed.css |
| `#c8e8f5` | 1 | public/css/app.css |
| `#c8edf9` | 1 | public/css/workspace.css |
| `#c9dce6` | 13 | public/css/workspace.css |
| `#c9e8f3` | 1 | public/css/workspace.css |
| `#cbdde8` | 2 | public/css/liquidation.css |
| `#cddce5` | 1 | public/css/app.css |
| `#cfe0e8` | 1 | public/css/workspace.css |
| `#cfe6ee` | 15 | public/css/about.css, public/css/app.css |
| `#cfeaf5` | 1 | public/css/about.css |
| `#d1d5db` | 2 | public/css/app.css |
| `#d2f2fa` | 1 | public/css/about.css |
| `#d3e1e8` | 3 | public/css/app.css |
| `#d3e2e9` | 1 | public/css/liquidation.css |
| `#d3e5ed` | 1 | public/css/workspace.css |
| `#d3eaf4` | 1 | public/css/workspace.css |
| `#d3edf7` | 1 | public/css/home.css |
| `#d4d4d4` | 4 | public/css/app.css, public/css/login.css |
| `#d5e2e8` | 1 | public/css/liquidation.css |
| `#d6e3ea` | 2 | public/css/workspace.css |
| `#d6e5ee` | 1 | public/css/app.css |
| `#d6eaf0` | 4 | public/css/app.css |
| `#d7edf3` | 1 | public/css/app.css |
| `#d7edf5` | 1 | public/css/app.css |
| `#d7edf8` | 1 | public/css/home.css |
| `#d8f1e4` | 2 | public/css/app.css |
| `#d8f1fa` | 1 | public/css/app.css |
| `#d9534f` | 1 | public/css/login.css |
| `#d9e0e4` | 2 | public/css/app.css |
| `#d9e7ef` | 1 | public/css/workspace.css |
| `#d9f4fb` | 1 | public/css/about.css |
| `#dbeef7` | 1 | public/css/app.css |
| `#dce7ec` | 3 | public/css/liquidation.css |
| `#dce8ee` | 16 | public/css/app.css, public/css/workspace.css |
| `#dcebf0` | 1 | public/css/app.css |
| `#ddd` | 1 | public/css/login.css |
| `#dff3fa` | 9 | public/css/about.css, public/css/app.css, public/css/workspace.css |
| `#dff3ff` | 1 | public/css/home.css |
| `#dff5fb` | 1 | public/css/app.css |
| `#e0ebf0` | 1 | public/css/workspace.css |
| `#e1e1e1` | 2 | public/css/home.css |
| `#e1eaf0` | 5 | public/css/app.css |
| `#e2ebef` | 7 | public/css/app.css, public/css/liquidation.css |
| `#e3f3f8` | 1 | public/css/app.css |
| `#e5eff4` | 1 | public/css/post-feed.css |
| `#e5f6eb` | 2 | public/css/app.css |
| `#e6f0f4` | 3 | public/css/app.css |
| `#e6f4f8` | 2 | public/css/app.css |
| `#e7edf1` | 1 | public/css/app.css |
| `#e7f1f5` | 3 | public/css/app.css |
| `#e8aaaa` | 2 | public/css/app.css |
| `#e8eef1` | 1 | public/css/app.css |
| `#e8f1f5` | 1 | public/css/app.css |
| `#e8f6ff` | 1 | public/css/home.css |
| `#eaf6ff` | 3 | public/css/home.css |
| `#eaf7fc` | 1 | public/css/app.css |
| `#eaf8fd` | 4 | public/css/post-feed.css, public/css/workspace.css |
| `#ebfaff` | 1 | public/css/about.css |
| `#edf5f8` | 4 | public/css/app.css, public/css/liquidation.css |
| `#edf5ff` | 1 | public/css/app.css |
| `#edf8ff` | 1 | public/css/app.css |
| `#edfaff` | 1 | public/css/workspace.css |
| `#eed98a` | 1 | public/css/app.css |
| `#eef8ff` | 1 | public/css/home.css |
| `#eff4f7` | 2 | public/css/app.css |
| `#eff9fd` | 1 | public/css/workspace.css |
| `#effaff` | 23 | public/css/about.css, public/css/app.css, public/css/workspace.css |
| `#f1f8fb` | 2 | public/css/workspace.css |
| `#f1f9fd` | 1 | public/css/workspace.css |
| `#f1faff` | 1 | public/css/workspace.css |
| `#f25022` | 1 | public/css/app.css |
| `#f2f7fa` | 1 | public/css/app.css |
| `#f2faff` | 1 | public/css/app.css |
| `#f3f6f8` | 1 | public/css/app.css |
| `#f3f7f9` | 2 | public/css/app.css, public/css/post-feed.css |
| `#f3f8fa` | 1 | public/css/liquidation.css |
| `#f4f8fa` | 3 | public/css/app.css |
| `#f4f9fb` | 1 | public/css/app.css |
| `#f4fbfe` | 1 | public/css/documents.css |
| `#f4fbff` | 1 | public/css/workspace.css |
| `#f5b5b5` | 1 | public/css/login.css |
| `#f5f5f5` | 2 | public/css/login.css |
| `#f5faff` | 1 | public/css/home.css |
| `#f6fbfe` | 1 | public/css/workspace.css |
| `#f6fbff` | 1 | public/css/app.css |
| `#f7f7f7` | 1 | public/css/app.css |
| `#f7fafb` | 3 | public/css/liquidation.css |
| `#f7fbfc` | 2 | public/css/about.css |
| `#f7fbfd` | 1 | public/css/errors.css |
| `#f7fcfe` | 1 | public/css/workspace.css |
| `#f7fdff` | 1 | public/css/home.css |
| `#f8fcfe` | 1 | public/css/workspace.css |
| `#f8fdff` | 1 | public/css/app.css |
| `#f9fcff` | 1 | public/css/app.css |
| `#f9fdff` | 1 | public/css/app.css |
| `#fbe4e1` | 1 | public/css/app.css |
| `#fdeaea` | 3 | public/css/app.css |
| `#ffb900` | 1 | public/css/app.css |
| `#ffe58f` | 1 | public/css/app.css |
| `#ffe5e5` | 1 | public/css/login.css |
| `#fff` | 81 | public/css/achievements.css, public/css/app.css, public/css/errors.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| `#fff1ef` | 1 | public/css/app.css |
| `#fff1f1` | 1 | public/css/post-feed.css |
| `#fff5cf` | 2 | public/css/app.css |
| `#ffffff` | 10 | public/css/app.css, public/css/home.css |
| `rgb(15 23 42 / 12%)` | 1 | public/css/app.css |
| `rgb(15 36 54 / 38%)` | 1 | public/css/workspace.css |
| `rgb(15 36 54 / 52%)` | 1 | public/css/workspace.css |
| `rgb(17 42 55 / 14%)` | 9 | public/css/about.css, public/css/app.css |
| `rgb(17 42 55 / 18%)` | 5 | public/css/app.css |
| `rgb(17 42 55 / 24%)` | 1 | public/css/app.css |
| `rgb(23 50 77 / 10%)` | 8 | public/css/about.css, public/css/app.css, public/css/documents.css, public/css/workspace.css |
| `rgb(23 50 77 / 12%)` | 6 | public/css/app.css, public/css/workspace.css |
| `rgb(23 50 77 / 13%)` | 2 | public/css/errors.css |
| `rgb(23 50 77 / 14%)` | 5 | public/css/about.css, public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| `rgb(23 50 77 / 16%)` | 2 | public/css/about.css, public/css/workspace.css |
| `rgb(23 50 77 / 17%)` | 1 | public/css/workspace.css |
| `rgb(23 50 77 / 18%)` | 7 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| `rgb(23 50 77 / 20%)` | 3 | public/css/workspace.css |
| `rgb(23 50 77 / 22%)` | 2 | public/css/workspace.css |
| `rgb(23 50 77 / 24%)` | 1 | public/css/workspace.css |
| `rgb(23 50 77 / 26%)` | 3 | public/css/workspace.css |
| `rgb(23 50 77 / 28%)` | 2 | public/css/workspace.css |
| `rgb(23 50 77 / 32%)` | 1 | public/css/workspace.css |
| `rgb(23 50 77 / 4%)` | 1 | public/css/workspace.css |
| `rgb(23 50 77 / 42%)` | 1 | public/css/workspace.css |
| `rgb(23 50 77 / 48%)` | 1 | public/css/app.css |
| `rgb(23 50 77 / 5%)` | 3 | public/css/liquidation.css, public/css/workspace.css |
| `rgb(23 50 77 / 6%)` | 3 | public/css/app.css, public/css/liquidation.css, public/css/post-feed.css |
| `rgb(23 50 77 / 7%)` | 4 | public/css/app.css, public/css/workspace.css |
| `rgb(23 50 77 / 8%)` | 7 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| `rgb(23 50 77 / 9%)` | 3 | public/css/liquidation.css, public/css/workspace.css |
| `rgb(23 50 77 / 90%)` | 1 | public/css/app.css |
| `rgb(255 255 255 / 18%)` | 2 | public/css/app.css, public/css/workspace.css |
| `rgb(255 255 255 / 20%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 28%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 38%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 40%)` | 1 | public/css/app.css |
| `rgb(255 255 255 / 48%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 58%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 60%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 65%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 68%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 72%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 8%)` | 1 | public/css/workspace.css |
| `rgb(255 255 255 / 90%)` | 1 | public/css/app.css |
| `rgb(51 65 85 / 90%)` | 1 | public/css/app.css |
| `rgba(0, 0, 0, 0.07)` | 1 | public/css/home.css |
| `rgba(0, 0, 0, 0.12)` | 2 | public/css/home.css, public/css/login.css |
| `rgba(23, 103, 131, 0.1)` | 1 | public/css/login.css |
| `rgba(5, 90, 156, 0.88)` | 1 | public/css/home.css |
| `rgba(8, 118, 201, 0.12)` | 1 | public/css/home.css |
| `rgba(8, 118, 201, 0.88)` | 1 | public/css/home.css |

## Appendix B � Distinct declaration values and file locations

These source-token inventories count each property value and record occurrence count and files. Compound shorthands are kept as written. They describe the current source before tokenization.

| Property family | Value | Uses | Files |
|---|---|---:|---|
| border | `#c5dbe6` | 1 | public/css/workspace.css |
| border | `#d9e7ef` | 1 | public/css/workspace.css |
| border | `0` | 46 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| border | `1px dashed #bfdfe8` | 1 | public/css/app.css |
| border | `1px solid #000` | 1 | public/css/app.css |
| border | `1px solid #3dafea` | 1 | public/css/workspace.css |
| border | `1px solid #7894a7` | 2 | public/css/post-feed.css |
| border | `1px solid #9cb5c5` | 2 | public/css/post-feed.css |
| border | `1px solid #9ed6b2` | 1 | public/css/app.css |
| border | `1px solid #a9cbdc` | 1 | public/css/workspace.css |
| border | `1px solid #b7cbd6` | 2 | public/css/app.css, public/css/post-feed.css |
| border | `1px solid #b7e5f6` | 1 | public/css/workspace.css |
| border | `1px solid #b8dce8` | 6 | public/css/app.css |
| border | `1px solid #b9cbd7` | 3 | public/css/app.css |
| border | `1px solid #b9cfdb` | 1 | public/css/workspace.css |
| border | `1px solid #b9d7e5` | 4 | public/css/workspace.css |
| border | `1px solid #b9dce9` | 1 | public/css/workspace.css |
| border | `1px solid #bfd5e0` | 1 | public/css/workspace.css |
| border | `1px solid #c4d6df` | 1 | public/css/liquidation.css |
| border | `1px solid #c5e5f2` | 1 | public/css/workspace.css |
| border | `1px solid #c8e6f2` | 2 | public/css/documents.css, public/css/post-feed.css |
| border | `1px solid #c8e8f5` | 1 | public/css/app.css |
| border | `1px solid #c9dce6` | 12 | public/css/workspace.css |
| border | `1px solid #c9e8f3` | 1 | public/css/workspace.css |
| border | `1px solid #cbdde8` | 2 | public/css/liquidation.css |
| border | `1px solid #cddce5` | 1 | public/css/app.css |
| border | `1px solid #cfe6ee` | 15 | public/css/about.css, public/css/app.css |
| border | `1px solid #cfeaf5` | 1 | public/css/about.css |
| border | `1px solid #d1d5db` | 1 | public/css/app.css |
| border | `1px solid #d3e1e8` | 2 | public/css/app.css |
| border | `1px solid #d3e2e9` | 1 | public/css/liquidation.css |
| border | `1px solid #d3e5ed` | 1 | public/css/workspace.css |
| border | `1px solid #d3edf7` | 1 | public/css/home.css |
| border | `1px solid #d4d4d4` | 4 | public/css/app.css, public/css/login.css |
| border | `1px solid #d5e2e8` | 1 | public/css/liquidation.css |
| border | `1px solid #d6e3ea` | 2 | public/css/workspace.css |
| border | `1px solid #d6eaf0` | 4 | public/css/app.css |
| border | `1px solid #d7edf3` | 1 | public/css/app.css |
| border | `1px solid #d7edf8` | 1 | public/css/home.css |
| border | `1px solid #d9e0e4` | 2 | public/css/app.css |
| border | `1px solid #dbeef7` | 1 | public/css/app.css |
| border | `1px solid #dce7ec` | 3 | public/css/liquidation.css |
| border | `1px solid #dce8ee` | 16 | public/css/app.css, public/css/workspace.css |
| border | `1px solid #dcebf0` | 1 | public/css/app.css |
| border | `1px solid #ddd` | 1 | public/css/login.css |
| border | `1px solid #e0ebf0` | 1 | public/css/workspace.css |
| border | `1px solid #e1e1e1` | 2 | public/css/home.css |
| border | `1px solid #e1eaf0` | 5 | public/css/app.css |
| border | `1px solid #e2ebef` | 6 | public/css/app.css, public/css/liquidation.css |
| border | `1px solid #e8aaaa` | 1 | public/css/app.css |
| border | `1px solid #f5b5b5` | 1 | public/css/login.css |
| border | `1px solid #fff` | 1 | public/css/app.css |
| border | `1px solid rgb(23 50 77 / 13%)` | 2 | public/css/errors.css |
| border | `1px solid rgb(23 50 77 / 14%)` | 1 | public/css/about.css |
| border | `1px solid rgb(23 50 77 / 16%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(23 50 77 / 18%)` | 3 | public/css/workspace.css |
| border | `1px solid rgb(23 50 77 / 20%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(23 50 77 / 22%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(23 50 77 / 26%)` | 3 | public/css/workspace.css |
| border | `1px solid rgb(23 50 77 / 28%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(255 255 255 / 18%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(255 255 255 / 40%)` | 1 | public/css/app.css |
| border | `1px solid rgb(255 255 255 / 48%)` | 1 | public/css/workspace.css |
| border | `1px solid rgb(255 255 255 / 60%)` | 1 | public/css/workspace.css |
| border | `1px solid transparent` | 3 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| border | `1px solid var(--ssite-line)` | 8 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| border | `1px solid var(--ssite-navy)` | 2 | public/css/app.css, public/css/workspace.css |
| border | `1px solid var(--workspace-border)` | 6 | public/css/workspace.css |
| border | `2px solid #64c5df` | 1 | public/css/about.css |
| border | `2px solid #7dd3ef` | 1 | public/css/about.css |
| border | `2px solid #e7f1f5` | 1 | public/css/app.css |
| border | `2px solid #fff` | 2 | public/css/workspace.css |
| border | `2px solid currentColor` | 2 | public/css/post-feed.css |
| border | `2px solid var(--ssite-blue)` | 1 | public/css/app.css |
| border | `2px solid var(--ssite-line)` | 11 | public/css/app.css, public/css/workspace.css |
| border | `2px solid var(--ssite-navy)` | 2 | public/css/app.css, public/css/workspace.css |
| border | `3px solid transparent` | 1 | public/css/app.css |
| border | `3px solid var(--ssite-line)` | 8 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/post-feed.css, public/css/workspace.css |
| border | `none` | 2 | public/css/login.css |
| border-radius | `0` | 5 | public/css/about.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `0 0 11px 11px` | 1 | public/css/app.css |
| border-radius | `0 3px 3px 0` | 1 | public/css/workspace.css |
| border-radius | `0.5rem` | 2 | public/css/app.css, public/css/errors.css |
| border-radius | `10px` | 7 | public/css/app.css, public/css/workspace.css |
| border-radius | `10px 24px 10px 24px` | 1 | public/css/about.css |
| border-radius | `12px` | 8 | public/css/app.css, public/css/liquidation.css, public/css/workspace.css |
| border-radius | `14px` | 3 | public/css/app.css, public/css/home.css |
| border-radius | `16px` | 2 | public/css/app.css |
| border-radius | `18px` | 1 | public/css/app.css |
| border-radius | `20px` | 1 | public/css/home.css |
| border-radius | `22px` | 1 | public/css/about.css |
| border-radius | `24px 10px 24px 10px` | 1 | public/css/about.css |
| border-radius | `2px` | 3 | public/css/app.css |
| border-radius | `3px` | 1 | public/css/app.css |
| border-radius | `4px` | 22 | public/css/about.css, public/css/app.css |
| border-radius | `50%` | 23 | public/css/app.css, public/css/home.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `5px` | 9 | public/css/app.css, public/css/home.css, public/css/login.css |
| border-radius | `6px` | 13 | public/css/app.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `7px` | 11 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `7px 7px 0 0` | 1 | public/css/app.css |
| border-radius | `8px` | 25 | public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `999px` | 15 | public/css/achievements.css, public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| border-radius | `9px` | 8 | public/css/app.css, public/css/liquidation.css, public/css/workspace.css |
| border-radius | `var(--post-feed-card-radius)` | 2 | public/css/post-feed.css |
| box-shadow | `0 0 0 2px rgba(23, 103, 131, 0.1)` | 1 | public/css/login.css |
| box-shadow | `0 10px 20px rgb(15 23 42 / 12%)` | 1 | public/css/app.css |
| box-shadow | `0 12px 24px rgb(23 50 77 / 12%)` | 1 | public/css/app.css |
| box-shadow | `0 12px 28px rgb(23 50 77 / 10%)` | 1 | public/css/about.css |
| box-shadow | `0 12px 28px rgb(23 50 77 / 8%)` | 1 | public/css/home.css |
| box-shadow | `0 12px 30px rgb(17 42 55 / 18%)` | 2 | public/css/app.css |
| box-shadow | `0 14px 34px rgb(23 50 77 / 18%), 0 2px 6px rgb(23 50 77 / 7%)` | 2 | public/css/app.css |
| box-shadow | `0 16px 48px rgb(17 42 55 / 24%)` | 1 | public/css/app.css |
| box-shadow | `0 1px 5px rgb(23 50 77 / 16%)` | 1 | public/css/about.css |
| box-shadow | `0 2px 7px rgb(23 50 77 / 10%)` | 1 | public/css/workspace.css |
| box-shadow | `0 2px 7px rgb(23 50 77 / 8%)` | 1 | public/css/workspace.css |
| box-shadow | `0 2px 8px rgb(23 50 77 / 10%)` | 1 | public/css/workspace.css |
| box-shadow | `0 2px 8px rgb(23 50 77 / 5%)` | 1 | public/css/liquidation.css |
| box-shadow | `0 3px 10px rgb(23 50 77 / 6%)` | 1 | public/css/post-feed.css |
| box-shadow | `0 3px 10px rgba(0, 0, 0, 0.07)` | 1 | public/css/home.css |
| box-shadow | `0 3px 12px rgb(23 50 77 / 5%)` | 1 | public/css/workspace.css |
| box-shadow | `0 3px 12px rgb(23 50 77 / 6%)` | 1 | public/css/liquidation.css |
| box-shadow | `0 3px 8px rgb(23 50 77 / 12%)` | 2 | public/css/app.css |
| box-shadow | `0 4px 12px rgb(17 42 55 / 14%)` | 1 | public/css/app.css |
| box-shadow | `0 4px 12px rgb(17 42 55 / 18%)` | 1 | public/css/app.css |
| box-shadow | `0 4px 12px rgb(23 50 77 / 14%)` | 1 | public/css/workspace.css |
| box-shadow | `0 4px 12px rgb(23 50 77 / 9%)` | 1 | public/css/workspace.css |
| box-shadow | `0 4px 14px rgb(23 50 77 / 5%)` | 1 | public/css/workspace.css |
| box-shadow | `0 4px 14px rgb(23 50 77 / 6%) !important` | 1 | public/css/app.css |
| box-shadow | `0 4px 15px rgba(0, 0, 0, 0.12)` | 1 | public/css/login.css |
| box-shadow | `0 5px 14px rgb(23 50 77 / 7%)` | 2 | public/css/app.css, public/css/workspace.css |
| box-shadow | `0 6px 18px rgb(23 50 77 / 8%)` | 2 | public/css/app.css |
| box-shadow | `0 7px 18px rgba(8, 118, 201, 0.12)` | 1 | public/css/home.css |
| box-shadow | `0 7px 20px rgb(23 50 77 / 9%)` | 1 | public/css/workspace.css |
| box-shadow | `0 8px 16px rgb(23 50 77 / 10%)` | 1 | public/css/app.css |
| box-shadow | `0 8px 20px rgb(17 42 55 / 14%)` | 8 | public/css/about.css, public/css/app.css |
| box-shadow | `0 8px 20px rgb(17 42 55 / 18%)` | 2 | public/css/app.css |
| box-shadow | `0 8px 20px rgb(23 50 77 / 10%)` | 1 | public/css/workspace.css |
| box-shadow | `0 8px 20px rgb(23 50 77 / 18%)` | 1 | public/css/post-feed.css |
| box-shadow | `0 8px 20px rgb(23 50 77 / 9%)` | 1 | public/css/liquidation.css |
| box-shadow | `0 8px 20px rgba(0, 0, 0, 0.12)` | 1 | public/css/home.css |
| box-shadow | `2px 2px 0 rgb(23 50 77 / 10%)` | 1 | public/css/documents.css |
| box-shadow | `3px 0 6px rgb(23 50 77 / 10%)` | 2 | public/css/app.css |
| box-shadow | `3px 4px 0 rgb(23 50 77 / 8%)` | 2 | public/css/workspace.css |
| box-shadow | `4px 0 14px rgb(23 50 77 / 8%)` | 1 | public/css/workspace.css |
| box-shadow | `4px 4px 0 rgb(23 50 77 / 14%)` | 1 | public/css/post-feed.css |
| box-shadow | `5px 0 22px rgb(23 50 77 / 4%)` | 1 | public/css/workspace.css |
| box-shadow | `5px 6px 0 rgb(23 50 77 / 12%)` | 2 | public/css/workspace.css |
| box-shadow | `5px 6px 0 rgb(23 50 77 / 18%)` | 1 | public/css/post-feed.css |
| box-shadow | `none` | 4 | public/css/app.css, public/css/workspace.css |
| box-shadow | `none !important` | 1 | public/css/app.css |
| box-shadow | `var(--hover-card-shadow)` | 1 | public/css/app.css |
| font-size | `.68rem` | 1 | public/css/app.css |
| font-size | `.72rem` | 1 | public/css/liquidation.css |
| font-size | `.75rem` | 2 | public/css/app.css |
| font-size | `.76rem` | 1 | public/css/liquidation.css |
| font-size | `.78rem` | 1 | public/css/app.css |
| font-size | `.7rem` | 1 | public/css/app.css |
| font-size | `.82rem` | 3 | public/css/app.css, public/css/liquidation.css |
| font-size | `.875rem` | 1 | public/css/app.css |
| font-size | `.88rem` | 3 | public/css/app.css, public/css/liquidation.css |
| font-size | `.8rem` | 4 | public/css/app.css, public/css/liquidation.css |
| font-size | `.94rem` | 1 | public/css/app.css |
| font-size | `.95rem` | 1 | public/css/liquidation.css |
| font-size | `.9rem` | 3 | public/css/app.css, public/css/liquidation.css |
| font-size | `0.65rem` | 1 | public/css/post-feed.css |
| font-size | `0.6875rem` | 1 | public/css/app.css |
| font-size | `0.75rem` | 2 | public/css/app.css, public/css/errors.css |
| font-size | `0.78rem` | 2 | public/css/achievements.css, public/css/app.css |
| font-size | `0.7rem` | 3 | public/css/app.css |
| font-size | `0.82rem` | 3 | public/css/activities.css, public/css/post-feed.css |
| font-size | `0.85rem` | 1 | public/css/achievements.css |
| font-size | `0.86rem` | 1 | public/css/post-feed.css |
| font-size | `0.875rem` | 4 | public/css/app.css |
| font-size | `0.88rem` | 1 | public/css/post-feed.css |
| font-size | `0.8rem` | 4 | public/css/app.css, public/css/errors.css, public/css/post-feed.css |
| font-size | `0.925rem` | 1 | public/css/errors.css |
| font-size | `0.9rem` | 1 | public/css/post-feed.css |
| font-size | `1.02rem` | 1 | public/css/liquidation.css |
| font-size | `1.05rem` | 5 | public/css/about.css, public/css/achievements.css, public/css/app.css, public/css/liquidation.css |
| font-size | `1.15rem` | 1 | public/css/home.css |
| font-size | `1.1rem` | 1 | public/css/app.css |
| font-size | `1.24rem` | 1 | public/css/app.css |
| font-size | `1.2rem` | 2 | public/css/app.css, public/css/post-feed.css |
| font-size | `1.5rem` | 2 | public/css/app.css |
| font-size | `10px` | 19 | public/css/about.css, public/css/app.css, public/css/workspace.css |
| font-size | `11px` | 40 | public/css/app.css, public/css/workspace.css |
| font-size | `12px` | 34 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/login.css, public/css/workspace.css |
| font-size | `12px !important` | 1 | public/css/workspace.css |
| font-size | `13px` | 39 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/workspace.css |
| font-size | `14px` | 27 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/login.css, public/css/workspace.css |
| font-size | `15px` | 11 | public/css/app.css, public/css/home.css, public/css/login.css, public/css/workspace.css |
| font-size | `16px` | 5 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/login.css |
| font-size | `17px` | 4 | public/css/home.css, public/css/workspace.css |
| font-size | `18px` | 7 | public/css/about.css, public/css/app.css, public/css/workspace.css |
| font-size | `19px` | 1 | public/css/home.css |
| font-size | `1rem` | 5 | public/css/about.css, public/css/app.css, public/css/errors.css, public/css/liquidation.css |
| font-size | `2.6rem` | 1 | public/css/app.css |
| font-size | `20px` | 3 | public/css/app.css, public/css/workspace.css |
| font-size | `21px` | 1 | public/css/workspace.css |
| font-size | `22px` | 1 | public/css/app.css |
| font-size | `23px` | 4 | public/css/home.css, public/css/workspace.css |
| font-size | `24px` | 5 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| font-size | `26px` | 3 | public/css/home.css, public/css/workspace.css |
| font-size | `27px` | 4 | public/css/home.css, public/css/workspace.css |
| font-size | `28px` | 2 | public/css/app.css, public/css/workspace.css |
| font-size | `29px` | 1 | public/css/home.css |
| font-size | `2rem` | 1 | public/css/app.css |
| font-size | `30px` | 4 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| font-size | `32px` | 2 | public/css/home.css |
| font-size | `34px` | 2 | public/css/home.css, public/css/login.css |
| font-size | `36px` | 1 | public/css/app.css |
| font-size | `40px` | 1 | public/css/home.css |
| font-size | `48px` | 1 | public/css/home.css |
| font-size | `7px` | 1 | public/css/app.css |
| font-size | `8px` | 1 | public/css/workspace.css |
| font-size | `9px` | 6 | public/css/liquidation.css, public/css/workspace.css |
| font-size | `clamp(1.15rem, 4vw, 1.45rem)` | 1 | public/css/post-feed.css |
| font-size | `clamp(1.35rem, 3vw, 1.9rem)` | 1 | public/css/liquidation.css |
| font-size | `clamp(1.3rem, 2.2vw, 2.1rem)` | 1 | public/css/home.css |
| font-size | `clamp(1.55rem, 5vw, 2rem)` | 1 | public/css/post-feed.css |
| font-size | `clamp(1.7rem, 3vw, 2.2rem)` | 1 | public/css/workspace.css |
| font-size | `clamp(1.7rem, 3vw, 2.35rem)` | 2 | public/css/app.css |
| font-size | `clamp(1.7rem, 3vw, 2.5rem)` | 1 | public/css/home.css |
| font-size | `clamp(1.8rem, 3vw, 2.7rem)` | 1 | public/css/about.css |
| font-size | `clamp(24px, 4vw, 32px)` | 1 | public/css/app.css |
| font-size | `clamp(2rem, 4vw, 3.5rem)` | 1 | public/css/errors.css |
| font-size | `clamp(2rem, 9vw, 2.75rem)` | 1 | public/css/errors.css |
| font-size | `clamp(5.5rem, 28vw, 8rem)` | 1 | public/css/errors.css |
| font-size | `clamp(6rem, 17vw, 13rem)` | 1 | public/css/errors.css |
| font-size | `inherit` | 1 | public/css/app.css |
| font-weight | `500` | 9 | public/css/app.css, public/css/workspace.css |
| font-weight | `600` | 41 | public/css/achievements.css, public/css/activities.css, public/css/app.css, public/css/errors.css, public/css/home.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| font-weight | `650` | 14 | public/css/app.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| font-weight | `700` | 70 | public/css/about.css, public/css/achievements.css, public/css/activities.css, public/css/app.css, public/css/errors.css, public/css/home.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| font-weight | `720` | 2 | public/css/workspace.css |
| font-weight | `750` | 12 | public/css/workspace.css |
| font-weight | `800` | 10 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| font-weight | `inherit` | 1 | public/css/app.css |
| letter-spacing | `-.015em` | 2 | public/css/workspace.css |
| letter-spacing | `.01em` | 1 | public/css/workspace.css |
| letter-spacing | `.025em` | 1 | public/css/workspace.css |
| letter-spacing | `-.025em` | 2 | public/css/workspace.css |
| letter-spacing | `-.02em` | 3 | public/css/workspace.css |
| letter-spacing | `.06em` | 1 | public/css/workspace.css |
| letter-spacing | `.07em` | 1 | public/css/app.css |
| letter-spacing | `.08em` | 2 | public/css/liquidation.css, public/css/workspace.css |
| letter-spacing | `.1em` | 1 | public/css/workspace.css |
| letter-spacing | `-0.015em` | 1 | public/css/app.css |
| letter-spacing | `-0.01em` | 1 | public/css/app.css |
| letter-spacing | `-0.02em` | 5 | public/css/app.css, public/css/liquidation.css, public/css/post-feed.css |
| letter-spacing | `0.035em` | 1 | public/css/errors.css |
| letter-spacing | `-0.035em` | 1 | public/css/errors.css |
| letter-spacing | `0.03em` | 1 | public/css/achievements.css |
| letter-spacing | `-0.03em` | 3 | public/css/about.css, public/css/app.css, public/css/home.css |
| letter-spacing | `0.04em` | 3 | public/css/about.css, public/css/app.css |
| letter-spacing | `-0.055em` | 1 | public/css/errors.css |
| letter-spacing | `0.12em` | 1 | public/css/home.css |
| line-height | `0.9` | 1 | public/css/errors.css |
| line-height | `1` | 5 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.1` | 2 | public/css/app.css, public/css/workspace.css |
| line-height | `1.12` | 1 | public/css/errors.css |
| line-height | `1.15` | 10 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.2` | 4 | public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/workspace.css |
| line-height | `1.25` | 3 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.3` | 1 | public/css/post-feed.css |
| line-height | `1.35` | 2 | public/css/app.css, public/css/home.css |
| line-height | `1.375` | 1 | public/css/app.css |
| line-height | `1.4` | 5 | public/css/app.css, public/css/home.css, public/css/liquidation.css |
| line-height | `1.45` | 3 | public/css/errors.css, public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.5` | 10 | public/css/about.css, public/css/home.css, public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.55` | 5 | public/css/post-feed.css, public/css/workspace.css |
| line-height | `1.6` | 5 | public/css/about.css, public/css/home.css, public/css/workspace.css |
| line-height | `1.65` | 2 | public/css/liquidation.css, public/css/workspace.css |
| line-height | `1.7` | 2 | public/css/about.css, public/css/home.css |
| line-height | `1.75` | 2 | public/css/app.css, public/css/errors.css |
| line-height | `1.8` | 1 | public/css/home.css |
| line-height | `11px` | 1 | public/css/app.css |
| line-height | `14px` | 1 | public/css/app.css |
| spacing shorthand | `0` | 88 | public/css/about.css, public/css/achievements.css, public/css/activities.css, public/css/app.css, public/css/errors.css, public/css/home.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 0 0 auto` | 1 | public/css/post-feed.css |
| spacing shorthand | `0 0 10px` | 2 | public/css/about.css, public/css/workspace.css |
| spacing shorthand | `0 0 12px` | 4 | public/css/about.css, public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 0 12px auto` | 1 | public/css/app.css |
| spacing shorthand | `0 0 14px` | 5 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 0 15px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 0 16px` | 3 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `0 0 18px` | 2 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `0 0 1rem` | 1 | public/css/errors.css |
| spacing shorthand | `0 0 20px` | 1 | public/css/home.css |
| spacing shorthand | `0 0 22px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 0 24px` | 1 | public/css/app.css |
| spacing shorthand | `0 0 25px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 0 28px` | 1 | public/css/about.css |
| spacing shorthand | `0 0 28px auto` | 1 | public/css/about.css |
| spacing shorthand | `0 0 32px` | 1 | public/css/app.css |
| spacing shorthand | `0 0 40px` | 1 | public/css/app.css |
| spacing shorthand | `0 0 8px` | 10 | public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 0 8px 12px` | 1 | public/css/app.css |
| spacing shorthand | `0 0 var(--post-feed-gap)` | 2 | public/css/post-feed.css |
| spacing shorthand | `0 10px` | 6 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 11px` | 2 | public/css/app.css, public/css/login.css |
| spacing shorthand | `0 12px` | 3 | public/css/workspace.css |
| spacing shorthand | `0 14px` | 6 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `0 15px` | 1 | public/css/about.css |
| spacing shorthand | `0 15px 0 3px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 16px 12px` | 1 | public/css/app.css |
| spacing shorthand | `0 1px` | 1 | public/css/app.css |
| spacing shorthand | `0 22px` | 1 | public/css/home.css |
| spacing shorthand | `0 22px 12px` | 1 | public/css/liquidation.css |
| spacing shorthand | `0 24px 0 40px` | 1 | public/css/app.css |
| spacing shorthand | `0 25px` | 1 | public/css/home.css |
| spacing shorthand | `0 26px 22px` | 2 | public/css/liquidation.css |
| spacing shorthand | `0 28px 0 10px` | 1 | public/css/post-feed.css |
| spacing shorthand | `0 2px` | 1 | public/css/app.css |
| spacing shorthand | `0 40px 0 20px` | 1 | public/css/app.css |
| spacing shorthand | `0 4px` | 3 | public/css/app.css, public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `0 5px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 -6px -6px` | 1 | public/css/app.css |
| spacing shorthand | `0 7px` | 1 | public/css/workspace.css |
| spacing shorthand | `0 9px` | 1 | public/css/app.css |
| spacing shorthand | `0 auto` | 9 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| spacing shorthand | `0 auto 25px` | 1 | public/css/home.css |
| spacing shorthand | `0 auto 6px` | 1 | public/css/about.css |
| spacing shorthand | `0 clamp(20px, 3vw, 42px)` | 1 | public/css/workspace.css |
| spacing shorthand | `0 max(var(--layout-page-gutter), calc((100vw - var(--layout-width-wide)) / 2))` | 1 | public/css/app.css |
| spacing shorthand | `0.5rem 0` | 1 | public/css/app.css |
| spacing shorthand | `0.65rem` | 1 | public/css/errors.css |
| spacing shorthand | `0.75rem 1.1rem` | 1 | public/css/errors.css |
| spacing shorthand | `0.875rem` | 2 | public/css/errors.css, public/css/post-feed.css |
| spacing shorthand | `1.5rem` | 1 | public/css/errors.css |
| spacing shorthand | `10px` | 20 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `10px 0` | 3 | public/css/app.css |
| spacing shorthand | `10px 0 0` | 1 | public/css/liquidation.css |
| spacing shorthand | `10px 0 5px` | 1 | public/css/app.css |
| spacing shorthand | `10px 12px` | 6 | public/css/about.css, public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `10px 14px` | 4 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `10px 14px 10px 0` | 1 | public/css/workspace.css |
| spacing shorthand | `10px 18px` | 2 | public/css/achievements.css, public/css/activities.css |
| spacing shorthand | `11px` | 4 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `11px 12px` | 1 | public/css/app.css |
| spacing shorthand | `11px 13px` | 1 | public/css/app.css |
| spacing shorthand | `11px 22px` | 1 | public/css/liquidation.css |
| spacing shorthand | `12px` | 32 | public/css/about.css, public/css/app.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `12px 0` | 2 | public/css/app.css |
| spacing shorthand | `12px 0 0` | 1 | public/css/workspace.css |
| spacing shorthand | `-12px 0 16px` | 1 | public/css/app.css |
| spacing shorthand | `12px 10px` | 1 | public/css/app.css |
| spacing shorthand | `12px 12px 0 0` | 1 | public/css/app.css |
| spacing shorthand | `12px 14px` | 4 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `12px 15px` | 2 | public/css/app.css, public/css/login.css |
| spacing shorthand | `12px 16px` | 2 | public/css/about.css, public/css/app.css |
| spacing shorthand | `12px 18px` | 1 | public/css/workspace.css |
| spacing shorthand | `12px 18px 16px` | 1 | public/css/app.css |
| spacing shorthand | `12px 20px` | 2 | public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `12px 22px` | 1 | public/css/home.css |
| spacing shorthand | `12px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `12px 9px` | 1 | public/css/app.css |
| spacing shorthand | `13px` | 6 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `13px 12px` | 2 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `14px` | 15 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `14px 10px 5px` | 1 | public/css/workspace.css |
| spacing shorthand | `14px 12px` | 2 | public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `14px 16px` | 1 | public/css/app.css |
| spacing shorthand | `14px 16px 14px 0` | 1 | public/css/workspace.css |
| spacing shorthand | `14px 8px` | 1 | public/css/workspace.css |
| spacing shorthand | `15px` | 1 | public/css/login.css |
| spacing shorthand | `15px 26px` | 1 | public/css/about.css |
| spacing shorthand | `16px` | 21 | public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `16px 0` | 1 | public/css/app.css |
| spacing shorthand | `16px 18px` | 1 | public/css/workspace.css |
| spacing shorthand | `16px 20px` | 2 | public/css/about.css, public/css/app.css |
| spacing shorthand | `16px 24px` | 1 | public/css/app.css |
| spacing shorthand | `16px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `17px 18px` | 1 | public/css/workspace.css |
| spacing shorthand | `17px 2px` | 1 | public/css/workspace.css |
| spacing shorthand | `17px 2px 0` | 1 | public/css/workspace.css |
| spacing shorthand | `18px` | 9 | public/css/app.css, public/css/home.css, public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `18px 0` | 1 | public/css/app.css |
| spacing shorthand | `18px 0 0` | 1 | public/css/app.css |
| spacing shorthand | `18px 12px !important` | 1 | public/css/workspace.css |
| spacing shorthand | `18px 16px` | 2 | public/css/liquidation.css |
| spacing shorthand | `18px 20px` | 4 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `18px 20px 12px` | 1 | public/css/workspace.css |
| spacing shorthand | `18px 22px` | 1 | public/css/liquidation.css |
| spacing shorthand | `18px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `1px` | 1 | public/css/liquidation.css |
| spacing shorthand | `1px 0 0` | 1 | public/css/about.css |
| spacing shorthand | `1px 5px` | 1 | public/css/app.css |
| spacing shorthand | `1rem` | 3 | public/css/app.css |
| spacing shorthand | `20px` | 7 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| spacing shorthand | `20px 22px` | 1 | public/css/liquidation.css |
| spacing shorthand | `20px 24px` | 2 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `20px 32px 28px` | 1 | public/css/login.css |
| spacing shorthand | `22px` | 4 | public/css/app.css, public/css/home.css, public/css/workspace.css |
| spacing shorthand | `22px 14px` | 1 | public/css/app.css |
| spacing shorthand | `22px 16px 16px` | 1 | public/css/workspace.css |
| spacing shorthand | `22px 20px` | 1 | public/css/about.css |
| spacing shorthand | `22px 26px` | 1 | public/css/liquidation.css |
| spacing shorthand | `22px 2px 0` | 1 | public/css/workspace.css |
| spacing shorthand | `24px` | 3 | public/css/about.css, public/css/app.css |
| spacing shorthand | `24px 0` | 1 | public/css/home.css |
| spacing shorthand | `24px 14px` | 1 | public/css/app.css |
| spacing shorthand | `24px 8px` | 1 | public/css/post-feed.css |
| spacing shorthand | `25px` | 2 | public/css/home.css |
| spacing shorthand | `26px` | 3 | public/css/app.css, public/css/liquidation.css |
| spacing shorthand | `26px !important` | 1 | public/css/app.css |
| spacing shorthand | `28px` | 1 | public/css/workspace.css |
| spacing shorthand | `28px 24px 31px` | 1 | public/css/app.css |
| spacing shorthand | `2px` | 6 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `2px 0` | 1 | public/css/post-feed.css |
| spacing shorthand | `2px 0 12px` | 1 | public/css/app.css |
| spacing shorthand | `2px 4px 16px` | 1 | public/css/workspace.css |
| spacing shorthand | `2px 6px 20px` | 1 | public/css/workspace.css |
| spacing shorthand | `30px` | 1 | public/css/home.css |
| spacing shorthand | `32px` | 3 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `32px 24px` | 1 | public/css/app.css |
| spacing shorthand | `36px` | 1 | public/css/workspace.css |
| spacing shorthand | `36px 16px` | 1 | public/css/app.css |
| spacing shorthand | `3px` | 7 | public/css/app.css, public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `3px 0 10px` | 1 | public/css/app.css |
| spacing shorthand | `3px 10px 3px 3px` | 1 | public/css/workspace.css |
| spacing shorthand | `3px 8px` | 1 | public/css/post-feed.css |
| spacing shorthand | `3px 9px` | 1 | public/css/achievements.css |
| spacing shorthand | `40px` | 2 | public/css/app.css |
| spacing shorthand | `45px 20px` | 1 | public/css/home.css |
| spacing shorthand | `48px` | 1 | public/css/app.css |
| spacing shorthand | `4px` | 16 | public/css/about.css, public/css/achievements.css, public/css/app.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `4px 0` | 4 | public/css/app.css |
| spacing shorthand | `4px 0 0` | 5 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `4px 10px` | 3 | public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `4px 11px 4px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `4px 12px` | 2 | public/css/workspace.css |
| spacing shorthand | `4px 14px` | 2 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `4px auto 0` | 1 | public/css/workspace.css |
| spacing shorthand | `50px` | 1 | public/css/home.css |
| spacing shorthand | `50px 20px` | 2 | public/css/home.css |
| spacing shorthand | `55px 25px` | 1 | public/css/home.css |
| spacing shorthand | `5px` | 6 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `5px 0` | 2 | public/css/app.css |
| spacing shorthand | `5px 0 0` | 6 | public/css/app.css, public/css/liquidation.css, public/css/workspace.css |
| spacing shorthand | `5px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `5px 8px` | 3 | public/css/app.css |
| spacing shorthand | `60px 30px` | 1 | public/css/home.css |
| spacing shorthand | `60px 35px` | 1 | public/css/home.css |
| spacing shorthand | `60px 6%` | 1 | public/css/home.css |
| spacing shorthand | `6px` | 15 | public/css/app.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `6px 0` | 1 | public/css/workspace.css |
| spacing shorthand | `6px 0 0` | 3 | public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `6px 10px` | 1 | public/css/liquidation.css |
| spacing shorthand | `6px 11px` | 1 | public/css/liquidation.css |
| spacing shorthand | `6px 14px 2px` | 2 | public/css/app.css |
| spacing shorthand | `6px 4px` | 1 | public/css/app.css |
| spacing shorthand | `-6px -6px 0` | 1 | public/css/app.css |
| spacing shorthand | `-6px -6px 6px` | 1 | public/css/app.css |
| spacing shorthand | `6px 8px` | 1 | public/css/app.css |
| spacing shorthand | `70px 60px` | 1 | public/css/home.css |
| spacing shorthand | `70px 8%` | 1 | public/css/home.css |
| spacing shorthand | `7px` | 7 | public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `7px 0 0` | 4 | public/css/workspace.css |
| spacing shorthand | `7px 10px` | 1 | public/css/app.css |
| spacing shorthand | `7px 10px 4px` | 1 | public/css/workspace.css |
| spacing shorthand | `7px 11px` | 1 | public/css/app.css |
| spacing shorthand | `7px 12px` | 1 | public/css/workspace.css |
| spacing shorthand | `7px 13px` | 1 | public/css/app.css |
| spacing shorthand | `7px 5px 5px` | 1 | public/css/liquidation.css |
| spacing shorthand | `7px 9px` | 1 | public/css/workspace.css |
| spacing shorthand | `8px` | 33 | public/css/about.css, public/css/activities.css, public/css/app.css, public/css/liquidation.css, public/css/login.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `8px 0` | 1 | public/css/app.css |
| spacing shorthand | `8px 0 0` | 5 | public/css/app.css, public/css/liquidation.css, public/css/post-feed.css, public/css/workspace.css |
| spacing shorthand | `8px 10px` | 3 | public/css/app.css |
| spacing shorthand | `8px 11px 4px` | 1 | public/css/app.css |
| spacing shorthand | `8px 12px` | 2 | public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `8px 14px` | 3 | public/css/app.css, public/css/post-feed.css |
| spacing shorthand | `8px 16px` | 3 | public/css/about.css, public/css/app.css |
| spacing shorthand | `9px` | 7 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `9px 11px` | 2 | public/css/app.css, public/css/workspace.css |
| spacing shorthand | `9px 14px` | 1 | public/css/app.css |
| spacing shorthand | `9px 15px` | 1 | public/css/app.css |
| spacing shorthand | `9px 2px` | 1 | public/css/app.css |
| spacing shorthand | `clamp(14px, 3.5vw, 22px)` | 2 | public/css/post-feed.css |
| spacing shorthand | `clamp(18px, 3vw, 32px)` | 1 | public/css/workspace.css |
| spacing shorthand | `clamp(2rem, 7vw, 7rem)` | 1 | public/css/errors.css |
| spacing shorthand | `var(--layout-page-top) 0 var(--layout-page-bottom)` | 2 | public/css/workspace.css |
| spacing shorthand | `var(--layout-page-top) auto` | 1 | public/css/app.css |
| spacing shorthand | `var(--layout-page-top) auto var(--layout-page-bottom)` | 5 | public/css/about.css, public/css/app.css, public/css/home.css, public/css/post-feed.css |
| spacing shorthand | `var(--layout-page-top) var(--layout-page-gutter) 0` | 2 | public/css/app.css |
| z-index | `1` | 3 | public/css/app.css |
| z-index | `1000` | 1 | public/css/app.css |
| z-index | `2` | 4 | public/css/app.css |
| z-index | `20` | 2 | public/css/app.css, public/css/workspace.css |
| z-index | `25` | 1 | public/css/about.css |
| z-index | `30` | 5 | public/css/app.css |
| z-index | `35` | 1 | public/css/about.css |
| z-index | `40` | 2 | public/css/app.css, public/css/workspace.css |
| z-index | `50` | 6 | public/css/app.css, public/css/workspace.css |
| z-index | `60` | 2 | public/css/app.css, public/css/workspace.css |
| z-index | `70` | 2 | public/css/app.css |
| z-index | `8` | 1 | public/css/post-feed.css |
| z-index | `80` | 1 | public/css/app.css |
| z-index | `auto` | 1 | public/css/workspace.css |
| z-index | `calc(var(--layer-notification) + 1)` | 1 | public/css/workspace.css |
| z-index | `var(--layer-dropdown)` | 3 | public/css/workspace.css |
| z-index | `var(--layer-header)` | 1 | public/css/workspace.css |
| z-index | `var(--layer-loading)` | 1 | public/css/workspace.css |
| z-index | `var(--layer-notification)` | 1 | public/css/workspace.css |
| z-index | `var(--workspace-layer-drawer)` | 1 | public/css/workspace.css |
| z-index | `var(--workspace-layer-scrim)` | 1 | public/css/workspace.css |
