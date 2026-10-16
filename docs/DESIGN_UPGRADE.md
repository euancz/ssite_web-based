# SSITE Design Upgrade Review

⚠️ DEGRADED: single-context (this review must stay a single direct audit; it was not split into separate agent assessments).

**Date:** 2026-10-16  
**Phase:** 1–3 only; no UI edits are approved or made.  
**Mode:** Operate for managers, reviewers, and forms; Read for public posts and search.  
**Decision gate:** Approve tweak IDs below. Approval of a package is not permission to start a build batch; each batch still needs your separate “go.”

## Executive summary

The site has a coherent SSITE system: Poppins, the blue/navy palette, a shared post-feed foundation, role-aware workflow tabs, private PDF attachment patterns, and distinct dashboards. The public pages work at the sampled widths. The next useful pass is narrow: synchronize the mobile menu’s accessible state, tune long-form reading width, consolidate a small set of existing values as semantic tokens, improve phone scanning of manager/review surfaces, and make “Needs review” the President’s first dashboard focus.

There is one important visual-context gap: the requested dark-blue rounded navigation pill, grid background, and pixel-style display headings are not visible in the current implementation. The live guest pages show a flat light-blue navigation row, a white page background, and Poppins headings. Since those identity cues are explicitly protected, I have not proposed replacing or recreating them from guesswork. Screenshots of the intended appearance are needed before any design decision concerning those cues.

### Phase 1: context and baseline

- Ran the bundled Impeccable `context` step. It found an incumbent visual implementation, no `PRODUCT.md` or `DESIGN.md`, and no `docs/DESIGN_UPGRADE_PROGRESS.md`. No project context files were created.
- Read the extant design review/progress, feed design, sort/filter plan/progress, position plan/progress, recommendations/progress, system overview, and “where is what” documentation before making recommendations. `docs/DESIGN_DIRECTION.md` and `docs/ROLES_AND_LAYOUT_PLAN.md` are not present.
- **Already planned / applied:** shared feed frame and post cards; role-gated composers, menus, tabs, and review actions; public/private PDF display rules; module sort/filter controls; status, empty-state, dialog, table, and dashboard components; role-specific dashboards; and notification/search patterns. These remain out of this proposal.
- Ran `php artisan route:list` before any edits. It contains **102 route entries**. The exact text snapshot is saved outside the repository at `C:\Users\lukee\.copilot\session-state\dba22e8d-57a1-49ae-8da6-9a6fb1ca80ec\files\route-list-before.txt`. No route files were changed.
- `phpunit.xml` forces `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`; `.env` points to MySQL database `ssitecms`; `.env.testing` is absent. **No tests were run**, and no migration, seeder, or application data write was performed.
- `docs/RECOMMENDATIONS.md` was already modified in the worktree. That pre-existing edit was read and preserved.
- The application was available at `http://localhost/SSITECMS/public/`. I inspected guest pages for Home, About, Articles, Activities, Achievements, Documents, and Search at 320, 768, 1024, and 1440 CSS pixels. None showed horizontal overflow. I also viewed Home at 320px and 1440px. Liquidation and signed-in-only pages were not opened in a browser.
- Source review covered the shared layout/navigation, home/about, all five post indexes and representative detail/forms, shared feed/UI components, search/notifications, profile completion, adviser/user/officer surfaces, and President review queue. Roles and protected content were not exercised through accounts.
- The bundled view detector reported three `broken-image` warnings on image-preview elements in the Article, Activity, and Achievement forms. Each preview is initially hidden and receives an object URL only after a local image is selected; these are detector false positives for the initial state.
- No Vite build was attempted. The active layout directly includes `public/css/app.css`; this phase changes documentation only.

### Research limits and provenance

You authorized a small number of network fetches. I read public WordPress documentation only; I did not inspect a logged-in WordPress or other publisher product. Accordingly, source-labelled observations below describe what those documentation pages state, not an independent live-product review. No package manager, `npx`, install, or code-fetch command was run.

Guest layout checks are browser observations. Authenticated screens and private Liquidation behavior are code-based observations only. The review did not verify true 200% browser zoom, keyboard-only operation, JavaScript-off behavior, reduced-motion rendering, empty authenticated data, long/missing private content, every status-color contrast, or role-specific HTML in authenticated responses.

### Before scores by page group

Scores use the Impeccable critique rubric (10 Nielsen heuristics, 0–4 each) and technical audit rubric (Accessibility, Performance, Theming, Responsive Design, Implementation Integrity, 0–4 each). They are source-and-browser estimates, not a claim of full WCAG certification or a role-authenticated usability test. The prior `docs/DESIGN_REVIEW.md` scores were used as the comparable baseline; this pass refreshed the home score because the stylesheet wiring and descriptive image-link names are now present in source.

| Page group | UX heuristic scores (Status / Real-world match / Control / Consistency / Error prevention / Recognition / Efficiency / Minimalism / Recovery / Help) | UX total | A11y | Perf. | Theme | Responsive | Integrity | Audit total |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Shared layout and navigation | 3 / 3 / 3 / 3 / 3 / 3 / 2 / 2 / 3 / 1 | 26/40 | 2 | 3 | 3 | 3 | 3 | 14/20 |
| Home | 3 / 2 / 3 / 3 / 3 / 3 / 2 / 2 / 3 / 2 | 26/40 | 3 | 2 | 2 | 3 | 3 | 13/20 |
| About and officer history | 3 / 4 / 3 / 3 / 3 / 3 / 2 / 3 / 3 / 2 | 29/40 | 2 | 3 | 3 | 3 | 3 | 14/20 |
| Articles, Activities, Achievements | 3 / 3 / 3 / 4 / 3 / 3 / 3 / 2 / 3 / 2 | 29/40 | 3 | 3 | 3 | 3 | 4 | 16/20 |
| Documents and Liquidation | 3 / 3 / 3 / 3 / 3 / 3 / 2 / 2 / 3 / 2 | 27/40 | 3 | 3 | 3 | 3 | 4 | 16/20 |
| Login and profile | 3 / 3 / 3 / 3 / 2 / 3 / 2 / 2 / 3 / 2 | 26/40 | 2 | 3 | 3 | 3 | 3 | 14/20 |
| Notifications and global search | 3 / 3 / 3 / 4 / 3 / 3 / 3 / 3 / 3 / 2 | 30/40 | 3 | 3 | 3 | 3 | 3 | 15/20 |
| Dashboards, managers, and review queue | 3 / 3 / 3 / 4 / 3 / 3 / 3 / 2 / 3 / 2 | 29/40 | 3 | 3 | 3 | 3 | 4 | 16/20 |

The lower confidence scores are concentrated in areas that require an authenticated screenshot or keyboard/zoom verification. No post-change scores exist because no design changes were approved or made.

### Verified findings and open issues

1. **P1 — Mobile navigation state is stale.** In `resources/views/layouts/app.blade.php`, the menu button starts with `aria-expanded="false"` and toggles the menu’s `hidden` class and icons inline, but does not update `aria-expanded` or its “Open menu” label. Screen-reader state can therefore disagree with the visible menu.
2. **P2 — Touch target shrinks at tablet widths.** `public/css/app.css` sets `.icon-button` to 44×44px, then reduces it to 40×40px at the 640px breakpoint. That affects touch use at widths such as 768px.
3. **P2 — Long-form text may use a wide measure.** Feed cards are centered at a 54rem maximum width; the full text remains inside the card width. A narrower measure for body copy can improve sustained reading without narrowing image or card framing.
4. **P2 — Liquidation index diverges from its documented shared-card implementation.** `docs/FEED_DESIGN.md` records the Liquidation feed as complete with shared-card anatomy, while the current `resources/views/liquidation/index.blade.php` still renders a custom report row. Preserve the custom amount/PDF semantics; confirm whether the visual difference is intentional before changing it. This is an existing plan/source discrepancy, not a silent fix.
5. **P2 — Role-manager/review tables need a phone interaction check.** `x-ui.data-table` provides a labelled, keyboard-focusable horizontal scroll region, but the adviser user manager and President queue were not rendered in a signed-in browser.
6. **P2 — Brand-reference discrepancy blocks safe styling decisions.** Source and guest screenshots do not show the requested dark-blue rounded pill, grid background, or pixel-style display headings. Do not add or substitute those cues without screenshots confirming the intended design.
7. **Existing behavior issue, out of scope:** the account menu contains a “Settings” button with no destination or behavior. Do not change it as a design tweak; product intent is required.

**Audit finding count:** P0: 0 · P1: 1 · P2: 5 · P3: 0. The inert Settings item is excluded because it is an existing behavior issue, not a design change in this scope.

The small blue `#3DAFEA` accent has a calculated **2.47:1** contrast ratio against white when used as text. In the inspected styles it is used for rules, borders, and other accents, not body text; no current text-role recoloring is proposed. If a future text use is desired, `#2A7EAA` is a same-hue candidate at approximately 4.5:1 against white. I will not adopt that shade or change any brand color without your approval.

## Phase 2: pattern benchmark

Verdicts refer to adapting a pattern to SSITE, never copying product-specific wording, visual styling, or layout.

### 1. Navigation and wayfinding

- **[general pattern]** Keep one persistent primary navigation with an unmistakable current-page state; it reduces “where am I?” effort. **Map:** retain the current pill/nav and `aria-current` links; **ADOPT AS A TWEAK** only for the missing mobile-menu state synchronization (T-01).
- **[general pattern]** Use a compact breadcrumb or section label on deeper manager/forms when the hierarchy is otherwise unclear. **Map:** role dashboards already use a page heading and the main nav remains available; **SKIP** new breadcrumb chrome on every feed page because it adds noise to the small, shallow site.
- **[general pattern]** Collapse secondary links behind a keyboard-operable disclosure on narrow screens. **Map:** the mobile nav already follows this structure; **ADAPT** its announced open/closed state, retaining the existing menu and mobile layout (T-01).

### 2. Content browsing

- **[observed: WordPress Posts screen documentation]** Search/filter controls, sortable columns, pagination, and bulk actions are described as ways to locate and manage posts. This reduces scanning cost for a growing library. **Map:** the five SSITE modules already have role-whitelisted workflow tabs and approved sort/filter controls; **ADOPT AS A TWEAK** means preserve these existing patterns, not add a second search or bulk-action system. Already planned/applied.
- **[observed: WordPress Posts screen documentation]** List and excerpt views expose different information density. This helps match browsing to task. **Map:** SSITE’s feed cards already use excerpts and full detail pages; **ADAPT** by keeping phone cards light and detailed reading on the show page. Do not add a new density toggle.
- **[general pattern]** Put title, owner/date, and status in a repeatable scan order; keep secondary actions grouped. **Map:** shared post cards and policy-gated row menus already do this; **ADOPT AS A TWEAK** only if the Liquidation discrepancy is confirmed as unintended.

### 3. Reading experience

- **[general pattern]** Use a comfortable line measure and clear title → metadata → body hierarchy; this reduces rereading on long posts. **Map:** retain the wide image/card frame but cap long-form text independently (T-02).
- **[observed: WordPress preview documentation]** The documented preview supports desktop, tablet, mobile, and manual canvas widths. This catches responsive reading issues before publish. **Map:** apply the same viewport-check habit to SSITE reviews, not an editor-style viewport selector; **ADAPT** to the existing review process.
- **[general pattern]** Related/next links and print controls are useful only when content volume and user tasks justify them. **Map:** the present organization feed is small and the request reserves header search as the only search; **SKIP** new related-content rails and share/print controls beyond already implemented links.

### 4. Composer and editing

- **[observed: WordPress Block Editor documentation]** The editor separates content, settings, preview, and save/publish state. Clear state feedback reduces uncertainty before submission. **Map:** SSITE’s existing forms already group fields, show inline validation, preview selected images, and report PDF filename/size; **ADAPT** those pieces without introducing drafts or a new editor.
- **[general pattern]** Lead with the required title, associate help/errors with the relevant field, and keep the primary submit action easy to find. **Map:** current module forms already follow that order; **ADOPT AS A TWEAK** only for consistency and visible submit/error state, preserving every validation rule and field.
- **[general pattern]** Warn before leaving a genuinely modified form, but do not block ordinary navigation or promise server-side saving. This prevents accidental loss without suggesting that a draft exists. **Map:** possible optional enhancement after the form screens are reviewed; **SKIP** for the current packages because it adds behavior and can create false warnings on file inputs.

### 5. Review and approval workflow

- **[general pattern]** A reviewer benefits from a clear pending queue, author/date/status context, and a preview before committing a decision. This lowers wrong-item approvals. **Map:** SSITE already has a President queue, per-item preview, pending-only approve/reject controls, and reason capture; **ADOPT AS A TWEAK** by preserving that order and clarifying its phone scan, with no new controls or review states.
- **[general pattern]** Confirmation and a required rejection reason are appropriate for consequential decisions; an empty queue should reassure rather than look broken. **Map:** the existing dialog/reason modal and empty message already cover these tasks; **ADAPT** only spacing/hierarchy if screenshots show friction. Already planned/applied.
- **[general pattern]** Decision history is useful for accountability but requires persistent records. **Map:** not part of this review; **SKIP** until an audit-history table and retention rules are approved.

### 6. Dashboards

- **[observed: WordPress Dashboard screen documentation]** “At a Glance” counts link to the corresponding management area, while an activity widget surfaces recent work. This makes summary figures actionable. **Map:** SSITE stat cards already link to their relevant lists, and dashboards have recent/needs-attention sections; **ADOPT AS A TWEAK** by prioritizing the next role action, not adding more widgets (T-05).
- **[general pattern]** Show only the counts, exceptions, and actions that fit the current role; first-run empty states should tell that role what to do next. **Map:** preserve President, officer, adviser, and superadmin boundaries and current empty states; **ADAPT** information order only.
- **[general pattern]** Use charts only when users must compare trends over time. **Map:** no current role task needs analytics charts; **SKIP** heavy dashboards.

### 7. Status and feedback

- **[observed: WordPress Block Editor documentation]** The documentation describes a saved-state label after successful save and a separate publish action. Explicit state feedback reduces uncertainty. **Map:** SSITE uses form errors and session status messages; **ADAPT** by ensuring the existing feedback remains adjacent to its task, not by adding autosave/draft behavior.
- **[general pattern]** Status should use text as well as color, and success/error feedback should be announced. This supports low-vision and assistive-technology users. **Map:** retain the existing text status badges, `role="status"` success messages, and `role="alert"` errors; **ADOPT AS A TWEAK** only if a specific contrast or announcement failure is confirmed.
- **[general pattern]** Keep motion short, non-blocking, and disabled or reduced under `prefers-reduced-motion`. **Map:** existing hover-device and reduced-motion rules remain; **SKIP** animation-heavy feedback.

### 8. Accessibility, responsive behavior, and polish

- **[observed: WordPress preview documentation]** A set of mobile/tablet/desktop preview widths encourages checking layout at more than one size. **Map:** the guest sweep confirms no overflow at 320/768/1024/1440px; **ADOPT** the same check for authenticated surfaces, which were not visually verified.
- **[general pattern]** Use visible keyboard focus, stateful disclosures, and touch targets around 44px. These make navigation usable without precision tapping or visual-only cues. **Map:** the mobile header button needs synchronized state and tablet-size controls are 40px; **ADOPT AS A TWEAK** (T-01).
- **[general pattern]** Reserve image geometry, avoid unnecessary eager loading below the fold, and animate only transform/opacity on hover-capable devices. This limits layout shift and motion cost. **Map:** post cards already reserve 16:9 dimensions and lazy-load non-detail imagery; keep those rules. **ADAPT** on any future image edit; do not add animation or change asset behavior in this review.

## Phase 3: proposed tweak list

IDs are stable. No row is approved by default, and no implementation starts until both an ID decision and a separate batch “go” are given.

### T-01 — Synchronize mobile navigation state and touch targets

- **Area / pages:** Navigation and wayfinding; shared header on all public and signed-in pages.
- **Intensity:** SUBTLE.
- **Pattern:** [general pattern] State-aware disclosure plus touch-sized controls; fits students using phones and preserves the existing shared menu.
- **Change:** Keep the current mobile menu and icons, but synchronize the button’s `aria-expanded` and accessible name with open/closed state; close it on Escape and return focus to the toggle. Keep header icon controls at least 44×44px for coarse pointers, including tablet widths. Use the existing `site-header`, `.mobile-menu-toggle`, and `.icon-button` styles; no new navigation or links.
- **Before → after:** `menu visually open / announced closed` → `menu visually open / announced open`; 40px coarse-pointer targets → at least 44px.
- **Wireframe:** `Logo | Search | Login | [menu 44×44]` → same header; only the disclosure state and hit area change.
- **Identity impact:** None; light-blue bar, nav pill, links, typography, imagery, and colors stay as-is.
- **Effort:** S. **Risk:** Low; check menu stacking, focus restoration, and button spacing at 320–1024px.
- **Needs from me:** None for the behavior. A screenshot is still needed to resolve the broader brand-cue discrepancy before any styling of the nav pill.
- **Depends on:** None.
- **Reversible:** Yes; revert the button state handling and touch-size rule.

### T-02 — Give long-form post text a reading measure

- **Area / pages:** Reading experience; full Articles, Activities, Achievements, Documents descriptions, and Liquidation notes.
- **Intensity:** SUBTLE.
- **Pattern:** [general pattern] A comfortable paragraph measure inside a wider media/card frame lowers reading fatigue, especially on desktop.
- **Change:** Constrain full-text copy only to a measured line length (target about 68 characters per line); leave the card, image, module strip, metadata, and mobile width unchanged. Add/use one shared `.post-card-body-full` reading-width token or rule in `public/css/post-feed.css`.
- **Before → after:** `full-width text follows the whole card` → `same post card; body copy has a readable measure`.
- **Wireframe:** `| title and metadata -------------------- |` / `| long body line continues ------------ |` → `| title and metadata -------------------- |` / `| body text ends at a comfortable measure |`.
- **Identity impact:** None; no font or color change.
- **Effort:** S. **Risk:** Low; long URLs, 320px wrapping, and existing escaped text must remain intact.
- **Needs from me:** None.
- **Depends on:** None.
- **Reversible:** Yes; remove the single shared measure rule/token.

### T-03 — Name the existing visual tokens without changing their values

- **Area / pages:** Design system; shared layout, cards, forms, status and action controls.
- **Intensity:** SUBTLE.
- **Pattern:** [general pattern] Semantic names for repeated color, type, spacing, shape, shadow, motion, and layer roles reduce drift while preserving a mature design.
- **Change:** Introduce only a small shared alias set in `public/css/app.css` for current palette roles, the existing type scale, repeated spacing/radii, offset-card shadow, motion durations, and z-index layers. Map aliases to existing values; do not change any rendered value, font, brand color, or status color. Keep `--ssite-line` decorative. Its 2.47:1 white contrast is not suitable for small text; no current text use was found. If blue text is later required, ask before using the same-hue candidate `#2A7EAA` (approximately 4.5:1 on white).
- **Before → after:** `same values repeated as literals/local tokens` → `same values referenced through a compact semantic vocabulary`.
- **Wireframe:** `--ssite-blue / local 14px / local 4px shadow` → `--color-brand / --radius-card / --shadow-card` mapped to those exact current values.
- **Identity impact:** None if aliases preserve current values. Do not use this ID to add the missing grid, pill, or display font.
- **Effort:** M. **Risk:** Low; accidental alias mismatch could change cascade, layer order, or contrast. Verify computed values before/after.
- **Needs from me:** None for value-preserving aliases. Approval is required before any brand-color value changes; none are proposed here.
- **Depends on:** None.
- **Reversible:** Yes; remove aliases and restore the existing declarations without data or route impact.

### T-04 — Improve phone scanning of manager and review tables

- **Area / pages:** Content browsing and review workflow; adviser user/officer managers, President review queue, and officer dashboard tables.
- **Intensity:** SUBTLE.
- **Pattern:** [general pattern] Keep tables when relationships between columns matter, but make the scroll region, title/identity column, and available row actions easy to locate on narrow screens.
- **Change:** After reviewing signed-in screenshots, refine the existing `x-ui.data-table` scroll cue, focus treatment, and phone spacing/column priority. Keep semantic tables, current pagination and filters, all existing controls, and server-side role gates; do not convert role controls into CSS-hidden markup or add actions.
- **Before → after:** `wide table with an accessible but visually quiet scroll region` → `same table and data, with an obvious keyboard/touch scroll affordance and clearer first-column scanning`.
- **Wireframe:** `| Account | Role | Program | Actions | →` → `| Account | Role | Program | Actions | →` with a visible, non-obstructing scroll cue and clear focus ring.
- **Identity impact:** None; retain existing data-table style and palette.
- **Effort:** M. **Risk:** Medium; sticky/overflow layers can clip menus or dialogs, and signed-in layouts remain unverified.
- **Needs from me:** Redacted screenshots of the adviser manager and President review queue at phone and desktop widths.
- **Depends on:** None.
- **Reversible:** Yes; revert the scoped CSS/markup adjustments; table semantics and routes remain unchanged.

### T-05 — Put the next review action first on role dashboards

- **Area / pages:** Dashboards; President and officer dashboards, with adviser/superadmin views checked for consistency.
- **Intensity:** CONFIDENT.
- **Pattern:** [observed: WordPress Dashboard screen documentation] Linked counts and recent activity make dashboard summaries actionable; [general pattern] high-priority work should precede passive totals.
- **Change:** Reorder only existing dashboard sections so President “Needs review” and officer “Needs attention” appear before general counts or tables on phones; keep adviser/superadmin role-assignment tasks first on their own screens. No data, new links, stats, permissions, or dashboard widgets are added. Reuse the current `dashboard-panel`, `dashboard-review-item`, and stat-card components.
- **Before → after:** `summary totals → actionable queue` → `actionable queue → summary totals`.
- **Wireframe:** `Stats → Needs review → Recent items` → `Needs review → Stats → Recent items`; adviser/superadmin retain their role-assignment-first order.
- **Identity impact:** None; hierarchy changes only.
- **Effort:** S. **Risk:** Low; confirm that the panel order remains useful at phone width and does not hide role-specific empty states.
- **Needs from me:** Redacted screenshots of President, officer, adviser, and superadmin dashboards, or approval to proceed from source-only review.
- **Depends on:** None.
- **Reversible:** Yes; restore the original Blade section order.

## Quick wins

| ID | Impact | Effort | Why |
|---|---|---|---|
| T-01 | High for touch and assistive-technology users | S | Fixes a verifiable stale disclosure state and 40px coarse-pointer controls without changing navigation. |
| T-02 | High for long-form reading | S | Narrows only paragraph copy; images/cards retain current width and identity. |
| T-05 | High for reviewers | S | Moves existing actionable work ahead of passive counts without adding functionality. |

## Signature move by page group

| Page group | Signature move | Status |
|---|---|---|
| Shared layout | Keep the common header and menu identity; make the mobile disclosure state truthful and tap-sized (T-01). | Proposed |
| Home | Let the current single featured story remain the clear lead, with the two supporting module cards beneath it. | Already present; no duplicate tweak |
| About | Let academic-year headings and the existing alternating-corner mission/vision cards carry the page’s personality; do not add a new roster control. | Already present; no duplicate tweak |
| Articles, Activities, Achievements | Give the full post body a calm reading measure while leaving the 16:9 media frame broad (T-02). | Proposed |
| Documents and Liquidation | Keep file name, size, and module facts together without ever exposing private storage paths. | Already planned/applied in `docs/FEED_DESIGN.md`; Liquidation index implementation differs and is flagged above |
| Login and profile | Keep the forced-profile gate’s clear task heading, grouped fields, and adjacent errors; request screenshots before adjusting the login surface. | Existing source pattern; visual decision held |
| Notifications and search | Keep the single global header search and distinct All/Unread and type-filter states. | Already present; no duplicate tweak |
| Dashboards, managers, and review | Put the role’s next action before summary numbers, without changing available controls (T-05). | Proposed |

The requested pixel-heading/grid/pill reference is not visible in the local implementation or screenshots. It is **not** a proposed signature move until you provide an image of the intended identity.

## Not recommended

- **Heavy analytics dashboards or charts:** the organization’s roles need review, assignment, and personal-post tasks, not an analytics suite.
- **Dense, many-column tables on student phones:** keep the existing tables where comparison matters, but avoid expanding them; improve their scroll affordance rather than shrinking type.
- **Dark mode as a default:** it was not requested and would require a full token, image, status-contrast, and multi-page verification pass. It remains optional only; rough effort M–L, risk medium.
- **Mega menus:** the five module destinations do not justify another navigation layer, especially on phones.
- **Animation-heavy effects, parallax, or hover-dependent actions:** these would compete with the existing offsets and brand cues, cost performance, and undermine reduced-motion/touch behavior.
- **Infinite scrolling, autosave drafts, scheduling, or bulk workflow redesign:** these change expected behavior and may require schema, permissions, or route decisions. Keep current pagination and submission flow.
- **Copying a specific publisher’s layout, colors, iconography, or wording:** only interaction patterns are considered; all recommendations are re-expressed in SSITE’s existing system.

## Design token plan

Values below describe the current stylesheet/source; proposed aliases do not change them.

| Token family | Today | Proposed |
|---|---|---|
| Color roles | `--ssite-blue: #8AD5F4`; `--ssite-blue-light: #C7ECF9`; `--ssite-navy: #17324D`; `--ssite-navy-soft: #3E5972`; `--ssite-line: #3DAFEA`; danger/surface tokens already exist. | Add semantic aliases only where they improve shared use: brand bar, pale surface, primary/muted text, decorative rule, danger, focus. Keep all source values unchanged. Do not use line blue for small text on white. Ask before changing any brand color. |
| Type | Poppins is loaded from Google Fonts and is the shared sans. Global body/headings use Poppins; many local sizes are `rem`, `clamp()`, or literal pixels. No pixel-display font is observed in the active heading styles. | Keep Poppins and the fallback stack. Name the existing body/meta/section/page-title roles only if doing T-03; no new font or type personality. |
| Spacing | Partial `--layout-space-1…6` scale: 4, 8, 12, 16, 24, 32px; page gutter is `clamp(12px, 1.6vw, 20px)`, top 24px, bottom 32px. | Reuse this scale; add no second spacing scale. |
| Radii | Local values; shared feed card radius is 14px, with other existing controls/cards using their current specific radii. | Name only genuinely repeated card/control radii, retaining every current value and exception (including About’s alternating corners and pills). |
| Shadows | Existing offset post-card shadow is `4px 4px 0 rgb(23 50 77 / 14%)`; hover card shadow is `0 8px 20px rgb(23 50 77 / 12%)`; other local shadows remain. | Give repeated offset and hover shadows semantic names; preserve direction, opacity, and offset. |
| Motion | Hover durations 200ms/350ms, `ease-out`; fine-hover rules and reduced-motion alternatives already exist. Header shadow uses 160ms. | Name existing fast/hover/image durations only; keep transform/opacity-only hover effects and reduced-motion behavior. |
| Z-index | Shared header 40, back-to-top 50, skeleton 1000; menus use local levels; native dialogs use the browser top layer. | Document semantic layer order and replace values only where equivalence is verified. Do not create a token that makes menus, bell, search, modals, or sticky header fall behind cards. |

## Packages

| Package | Included | Total effort | Risk |
|---|---|---|---|
| **Subtle only** | T-01, T-02, T-03, T-04 | 2S + 2M | Low–medium; T-04 needs authenticated screenshots and stacking checks. |
| **Subtle plus Confident** | T-01 through T-05 | 3S + 2M | Medium; includes all subtle items plus dashboard information-order changes and phone checks. |

## Approval table

Leave the Decision column blank while reviewing. Use **Approve**, **Later**, or **Reject** for each ID, or approve a package and then list exceptions. Example: “Approve the Subtle package; plus T-05; Reject T-03.” No change will be made until an approved batch plan is posted and you say “go.”

| ID | Title | Intensity | Effort | Risk | Decision (Approve / Later / Reject) |
|---|---|---|---|---|---|
| T-01 | Synchronize mobile navigation state and touch targets | SUBTLE | S | Low | |
| T-02 | Give long-form post text a reading measure | SUBTLE | S | Low | |
| T-03 | Name the existing visual tokens without changing their values | SUBTLE | M | Low | |
| T-04 | Improve phone scanning of manager and review tables | SUBTLE | M | Medium | |
| T-05 | Put the next review action first on role dashboards | CONFIDENT | S | Low | |

## Optional later

These are not included in either package and must not be implemented in this review.

| Feature | Prerequisite approval / schema decision |
|---|---|
| Drafts or scheduled posts | Decide lifecycle and who can view/edit drafts; likely needs draft/schedule state and publish timestamp columns on each external content table, or a new shared `post_drafts` table. Define how drafts interact with President approval before any SQL. |
| Review decision history/audit trail | Approve retention, privacy, and reviewer visibility; likely a `post_review_events` table keyed by post type and that module’s custom primary key, with actor, transition, reason, and timestamp. |
| Comments or reactions beyond existing likes | Approve moderation, notifications, ownership, and who can view/respond; requires new comment/reaction tables and permission decisions. Existing likes must remain unchanged. |
| Calendar or event export | Decide whether event and achievement dates qualify, timezone rules, and public visibility; likely needs a new route/view and possibly export format, but existing activity dates may avoid new columns. |
| Dark mode | Explicitly request it first. Estimate M–L; map every semantic color, badge, focus ring, image treatment, and private/public surface, then verify all page groups and contrast. Not a default. |

## Limits of this review and screenshots that would help

- Live/browser evidence covers guest Home, About, Articles, Activities, Achievements, Documents, and Search at 320/768/1024/1440px. No horizontal overflow was detected. This is not a complete keyboard, contrast, reduced-motion, JavaScript-off, or 200%-zoom audit.
- Private Liquidation, login validation, profile completion, notification dropdown, officer/adviser/superadmin managers, and the President review queue were reviewed from Blade/CSS only. No accounts were created or used.
- Empty private lists, long private titles, rejected/archived private posts, missing PDFs, and role-specific page-source controls were not tested. No status or permission behavior was changed.
- Please provide redacted screenshots (no student names, emails, IDs, or private PDF details) of: (1) the intended header/nav/grid/pixel-heading appearance; (2) login popover open and any validation error; (3) President review queue/dashboard; (4) officer dashboard; (5) adviser and superadmin dashboards/user manager; and (6) the forced profile form and document/Liquidation forms. Phone and desktop captures are most useful. These are needed before any visual change to login-only or role-restricted surfaces.
- The light-blue brand colors themselves have not been changed. The line-blue contrast note above applies only to text use; current inspected uses are decorative.
- Baseline route snapshot is retained outside the worktree; no after-route diff exists because no routes were changed.

## References used

- WordPress.org, [Posts screen](https://wordpress.org/documentation/article/posts-screen/) — documentation on list columns, sorting, filtering, excerpts, pagination, and bulk actions.
- WordPress.org, [WordPress Block Editor](https://wordpress.org/documentation/article/wordpress-block-editor/) — documentation on editor workspace, save, view, and publish state.
- WordPress.org, [How to use the View feature](https://wordpress.org/documentation/article/how-to-use-the-preview-function/) — documentation on desktop/tablet/mobile preview widths.
- WordPress.org, [Dashboard screen](https://wordpress.org/documentation/article/dashboard-screen/) — documentation on linked summary counts, activity, and quick-draft widgets.

These links support only the explicitly `[observed: ... documentation]` patterns above. They are not claims that the live WordPress application was inspected.
