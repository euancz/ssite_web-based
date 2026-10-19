# Icons, Motion, and Design Improvements Plan

**Prepared:** 2026-10-17  
**Phase:** Audit and proposal. No new application-source edits are approved by this plan.  
**Decision gate:** Choose a package and answer the preferences at the end before implementation batches begin.

## 1. Scope, constraints, and current worktree

This plan extends the incumbent SSITE identity: SSITE blue/navy, light-blue workspace top bar, Poppins, pixel-style display headings, subtle grid, offset-shadow cards, SSITE logo and wolf imagery. It does not introduce a new visual world, change routes, or alter role and content behavior.

Preserve all applicable decisions in `docs/DESIGN_SYSTEM.md`, `docs/WORKSPACE_PLAN.md`, `docs/DESIGN_UPGRADE.md`, `docs/FEED_DESIGN.md`, `docs/SORT_FILTER_PLAN.md`, `docs/RECOMMENDATIONS.md`, `docs/SYSTEM_OVERVIEW.md`, and `docs/WHERE_IS_WHAT.md`, including the separate workspace shell, semantic tokens, public pill, sticky navigation, nav dots, header search, feed cards, private-file authorization, and server-side capability checks.

The working tree already contains a partial icon/motion attempt from before this planning checkpoint. It is not considered complete or approved implementation: `resources/views/components/icon.blade.php` has a hand-maintained shape map rather than verified Lucide sources; `resources/js/motion.js` does not import Motion; `resources/css/app.css` starts marked elements hidden; and no Blade layout currently loads the Vite entry. These are called out as implementation risks, not treated as finished work. The unrelated untracked `docs/ROUTES_BEFORE.txt` is not part of this plan and must be preserved.

## 2. Phase 0 preflight

| Item | Evidence / result |
|---|---|
| Branch | `icons-motion-polish` |
| Runtime | Node `v24.15.0`; npm `11.12.1` |
| Vite | Configured in `vite.config.js` for `resources/css/app.css` and `resources/js/app.js`. |
| Existing bundle | Current build: JS 53.01 kB (20.02 kB gzip); CSS 34.90 kB (6.91 kB gzip). `public/build/` is ignored by Git. This measurement is before any Motion API import. |
| Build | `npm run build` succeeded (Vite 6.4.4, 59 modules). |
| Current package edits | Motion `14.3.0` is pinned in `dependencies`; Lucide Static `1.59.0` is currently a temporary pinned `devDependency`; `package-lock.json` is new/untracked and must be retained. Existing unrelated dependency ranges were not changed. |
| Package check | `npm view motion scripts maintainers version --json` and `npm view lucide-static scripts maintainers version --json` were run. Motion reports MIT, maintainers Matt Perry / Motion; Lucide Static reports ISC, maintainer Eric Fennis. Their package scripts were inspected. Installs used `--ignore-scripts`; no package lifecycle scripts were intentionally run. |
| Release age | Motion `14.3.0` published 2026-10-15; Lucide Static `1.59.0` published 2026-10-17. The user explicitly waived the 14-day minimum-age check. |
| License | Motion: MIT. Lucide Static: ISC; its license also contains MIT notices for icons derived from Feather. If Lucide SVGs are copied into the project, add `THIRD_PARTY_NOTICES.md` with both required notices. |
| Audit | `npm audit --json`: 0 vulnerabilities (0 info/low/moderate/high/critical). |
| Lucide source size | The temporary package contains 2,159 SVG files totaling 1,048,023 bytes. Only selected SVG path data will be copied into the Blade map; the package will then be removed. |
| CSP | No Content-Security-Policy or `script-src` configuration was found in `config/`, `app/`, or `bootstrap/`. Hosting-level CSP remains unknown. The proposed integration uses the normal Vite module entry and no inline bootstrap script. |
| Asset delivery | `@vite` is not currently used by the Blade layouts. Layouts link legacy `public/css/*.css` directly; the Vite build currently is not included by those pages. Build on this Node-equipped development/build host, deploy `public/build/` with the release, and wire the Vite JS entry in the approved batch. Production-host Node availability is unknown and is not required when prebuilt assets are deployed. |
| Route baseline | Existing `audit/route-list-before.txt` records the before snapshot (113 routes, also documented in existing progress files). Expected route-list diff: empty. |
| JavaScript | `resources/js/bootstrap.js` configures Axios; `resources/js/app.js` imports bootstrap and the partial motion module. Existing layouts also have inline menu/search/notification code. No Motion package API is currently imported. |
| Existing motion | CSS transitions are used across the public/workspace styles; skeleton shimmer and feed skeleton pulse keyframes exist; `public/css/app.css` also contains view-transition rules. No new page transitions, parallax, autoplay, cursor effects, or looping decoration are proposed. |
| Database / tests | No tests, migrations, seeders, database reads/writes, or database-refreshing commands were run. No such command is part of the planning phase. |

## 3. Phase 1 audit evidence

### Impeccable and browser evidence

`impeccable context --target resources/views/workspace/dashboard.blade.php` loaded successfully and identified a live Operate-mode workspace. This project’s installed Impeccable CLI exposes `detect`, not standalone `critique` or `audit` commands; attempts to invoke those command names returned “Unknown command.” The skill’s critique/audit references were used for the source review instead of claiming those commands ran.

The global detector run on `resources/views` reported three `broken-image` warnings for empty `src` on the Article, Activity, and Achievement form preview images. The preview script fills these after file selection, matching the already-recorded detector false positives in `docs/DESIGN_REVIEW.md`; they are unrelated to icons/motion. The detector run on the dashboard/icon targets was clean.

The integrated browser could not inspect the app: `http://localhost` served the XAMPP welcome page rather than SSITECMS. Consequently no page is claimed browser-verified at 320, 768, or 1440px. Authenticated/login-only pages were reviewed from source; no accounts were created and no authentication state was changed.

### Provisional source-only scores

These are directional 0–4 source-review scores, not rendered contrast or browser scores. Impeccable’s critique/audit subcommands and the application browser were unavailable. Re-score in the implementation audit only after a usable local application/browser is provided.

| Page group | Accessibility | Performance | Theming | Responsive | Implementation integrity | Total |
|---|---:|---:|---:|---:|---:|---:|
| Public pages and feed | 3 | 3 | 3 | 3 | 3 | 15/20 |
| Authentication and profile | 3 | 3 | 3 | 3 | 3 | 15/20 |
| Notifications and search | 2 | 3 | 3 | 3 | 2 | 13/20 |
| Error pages | 3 | 4 | 3 | 3 | 3 | 16/20 |
| Workspace shell | 3 | 3 | 3 | 3 | 3 | 15/20 |
| Workspace dashboards | 3 | 3 | 3 | 3 | 3 | 15/20 |
| Management lists and forms | 3 | 3 | 3 | 3 | 3 | 15/20 |

Source-based evidence: the workspace component already hides decorative SVGs from assistive technology and many buttons have text/ARIA labels; however, visible hover/focus tooltips are not consistent on icon-only controls. Search suggestions inject Unicode glyphs. Several different hand-authored SVGs and a workspace-only icon component coexist. The layout does not load Vite. The workspace metric hover rule also applies to static articles, which can make them appear interactive. Existing design tokens and responsive styles are substantial strengths. Rendered contrast, zoom, target sizes, JS-off behavior, and role-specific HTML remain unverified.

### Inventory

- 39 existing `<x-workspace.icon>` call sites across workspace navigation, dashboards, review, and officer/user management; several values come from fixed `config/workspace.php` entries.
- 22 direct inline SVG elements in nine view files, including duplicate search, account, error-arrow, overflow-menu, PDF, about-disclosure, and footer-contact shapes.
- One search-suggestions JS map injects six symbol glyphs: articles `▤`, activities `◷`, achievements `★`, documents `▧`, liquidation `◫`, officers `♙`, plus a bullet fallback.
- Notification JS renders status/category initials rather than shared SVGs; this is dynamic icon-like decoration to replace with cloned static templates.
- No additional emoji or icon font source was found in the searched views, JS, and CSS. SSITE/college marks and wolf imagery are brand imagery, not UI icons, and remain unchanged. Ordinary punctuation (for example ellipses in sentences) is not an icon.
- Existing CSS transitions and keyframes are already present; Motion is not in use. One existing view-transition rule set is noted but will not be extended.

## 4. Dependency recommendation

### Pin and package choice

| Package | Exact version | License | Maintainers | Install scripts | Intended footprint |
|---|---:|---|---|---|---|
| `motion` | `14.3.0` | MIT | Matt Perry; Motion | Metadata lists build/dev/test/clean/prepack/postpublish scripts. Install with scripts disabled; no script is needed for Vite use. | Runtime dependency; import only `animate` and `inView` from the vanilla JS API. Incremental production gzip must be measured after integration; ceiling is <25 kB gzip. |
| `lucide-static` | `1.59.0` | ISC; included Feather-derived icons carry MIT terms | Eric Fennis | Metadata lists build/typecheck/clean scripts. Install with scripts disabled; no script is needed to read the shipped SVG files. | Temporary local extraction tool only. Remove from `package.json` and lockfile after copying selected official SVG paths. No Lucide package or CDN request ships at runtime. |

**Recommendation: Lucide option A (inline SVG Blade component, zero runtime dependency).** It preserves server-rendered icons on slow connections and without JavaScript, avoids a runtime request/library, and keeps Vite’s Motion bundle independent of icons. Option B is not proposed: no Packagist candidate has been researched or verified for license, maintenance, Laravel/PHP support, and exact compatible version.

The user approved package installation and later waived the release-age check. Installs used exact pins and `--ignore-scripts`. Both `npm view <package> scripts maintainers` results were inspected. `npm audit` returned zero vulnerabilities. The final lockfile should retain Motion but not the temporary Lucide Static package.

### Commands already approved/run

```powershell
npm view motion scripts maintainers version --json
npm view lucide-static scripts maintainers version --json
npm install --save-exact --ignore-scripts motion@14.3.0
npm install --save-exact --save-dev --ignore-scripts lucide-static@1.59.0
npm uninstall --save-dev --ignore-scripts lucide-static
npm audit --json
npm run build
```

Lucide Static has since been reinstalled temporarily, pinned, and audited for extraction. Remove it after the icon-map batch; do not commit `node_modules/`.

## 5. Icon system proposal

### API and ownership

- One public Blade component: `<x-icon name="search" size="md" />`. Keep `<x-workspace.icon>` as a thin compatibility wrapper during the area-by-area replacement, then remove it only after every caller is migrated.
- One static switch/map in `resources/views/components/icon.blade.php` owns all SVG path shapes and the shared root attributes: `viewBox`, `fill="none"`, `stroke="currentColor"`, `stroke-linecap="round"`, `stroke-linejoin="round"`, default stroke width token, focus behavior, and sizes.
- Allowed size tokens: 16, 20, and 24px; larger 48/64px tokens only for approved empty-state illustrations/icons. Default 20px. Default stroke width: 2.0. Use `currentColor`; use existing CSS color tokens on the wrapper rather than component-specific color props.
- SVG names are literals in templates or fixed configuration only; never derive names from a request, database text, or notification payload. Dynamic server responses select among static `<template>` fragments through a local allow-listed category/status map and clone the pre-rendered SVG. Unknown names render nothing and log at debug level only in local mode.
- All icons are decorative (`aria-hidden="true"`, `focusable="false"`). Visible text remains the source of meaning; icon-only controls keep a text/ARIA label and gain a visible hover/focus tooltip. Keep interactive targets at least 44×44px. Align inline icon/text with flex and the existing spacing tokens.
- Use only official Lucide SVG path data validated against the installed `lucide-static@1.59.0` catalog. Add ISC and Feather-derived MIT notices in `THIRD_PARTY_NOTICES.md`.

### Replacement map

| Concept | Lucide name | Current / planned surfaces |
|---|---|---|
| Dashboard | `layout-dashboard` | Workspace sidebar/dashboard |
| Article/document/PDF | `file-text` | Workspace create/library; public and workspace attachments; Documents and search suggestions |
| Archive | `archive` | Workspace library, archived status |
| Pending/time | `clock` | Workspace dashboard, adviser counts, notifications |
| Activity/date | `calendar-days` | Workspace module navigation and year filter; search suggestions |
| Achievement | `award` | Workspace module navigation and search suggestions |
| Liquidation/report | `chart-column-increasing` | Workspace module navigation and search suggestions |
| People/roster/officers | `users` | Workspace administration, adviser pages, search suggestions |
| Approved/success | `circle-check` | Review queue, approved notifications, status badges if E-05 is approved |
| Rejected | `circle-x` | Rejected notifications/status badges if E-05 is approved |
| Search | `search` | Public header, search form, suggestions |
| Notifications | `bell` | Public and workspace notification controls |
| Menu/open navigation | `menu` | Public mobile menu and workspace rail/drawer |
| Close | `x` | Public mobile navigation close state |
| Right/forward | `chevron-right` | Workspace action links, navigation and error-page return link |
| Down/disclosure | `chevron-down` | About officer terms and dropdown affordances |
| External destination/preview | `external-link` | Workspace preview/site actions |
| Add | `plus` | Officer roster action |
| Person/profile | `user-round` | Account menu/profile action |
| Settings | `settings` | Existing account menu item |
| Sign out | `log-out` | Existing account menu |
| Location | `map-pin` | Public footer address |
| Email | `mail` | Public footer email |
| External social destination | `external-link` | Footer link currently represented by a Facebook-branded path; Lucide has no Facebook trademark icon. The link text already identifies the destination. |
| Overflow actions | `ellipsis` | Public and workspace post row menus |
| Like | `heart` | Existing feature-flagged Like action where rendered |
| Download | `download` | Existing private-file action where rendered |

The exact old-SVG-to-concept map will be checked against source context during each migration batch. Any additional, verified UI concept discovered in the full area-by-area inventory will be added to this table before its change; no guessed Lucide names or second icon family will be introduced.

## 6. Motion system proposal

### Structure and resilience

- One small `resources/js/motion.js`, imported from `resources/js/app.js`; import only `animate` and `inView` from `motion`. Keep the logic in small functions with short explanatory comments.
- Use `data-motion="reveal"` on selected below-fold feed cards/sections; `data-motion="stagger"` on a list container; limit a stagger to the first 12 visible items and cap total delay. Use a `data-motion="pop"` hook only for a notification badge count change if approved.
- Reuse the existing `--motion-card`, `--motion-header`, and `--motion-easing` tokens for durations/easing; one short tween recipe (about 220ms) and one restrained spring recipe are shared. Animate only `transform` and `opacity`; do not animate width, height, margins, or position.
- No global CSS opacity-zero state. No inline early script or CSP exception. Content and inline Blade SVGs render in their final visible state by default. Motion attaches after the deferred Vite module loads and only starts on targets that begin below the viewport. If the module fails, is blocked, or JavaScript is disabled, the server-rendered page remains fully readable and navigable.
- Check `prefers-reduced-motion` before starting; when it is on, skip Motion and expose final states immediately. Listen for changes while the page is open; stop active animations and restore final styles when the preference changes to reduce motion.
- Above-the-fold content is never animated in. In particular, do not animate the first viewport of dashboard metrics or feed content. CSS continues to own existing hover, focus, drawer, and menu transitions. Do not convert menus/drawers/modals to Motion unless CSS cannot satisfy a documented need and a later batch is approved.
- Effects are short, interruptible, capped for long feeds, and work on phones without relying on hover. No count-up until selected; if later approved, the actual number remains in HTML from the start and must survive cancellation/failure unchanged.
- Vite build output is deployed under `public/build/`; the host does not need Node if the build is run here and the generated manifest/assets travel with the release. Route diff remains empty.

### Proposed effect locations

| Effect | Location | State |
|---|---|---|
| Scroll reveal | Public and workspace feed cards/sections that begin below the fold; one-time reveal, only first 12 cards | Recommended |
| Stagger | A related dashboard/list group only when it begins below the fold; max 12 items and bounded total delay | Recommended |
| Notification badge pop | Existing notification-count change only; no continuous animation | Optional preference |
| Sidebar, dropdown, modal, toast enter/exit | Existing CSS transitions/focus behavior remain in charge | Not recommended for Motion in this batch |
| Stat count-up | No default proposal; value announcement/cancellation adds avoidable complexity | Optional, off by default |
| Page transitions, parallax, scroll-jacking, autoplay, cursor effects, looping decoration | None | Not recommended |

## 7. Evidence-led improvement proposals

Intensity labels use the requested terms **SUBTLE** and **CONFIDENT**. “Depends on” records actual prerequisites. No proposal alters controllers, routes, permissions, data, validation, file access, or notification/search logic.

| ID | Title; area/pages | Problem and proposed change | Icon/motion; wireframe if layout changes | Effort / risk | Depends on | Needs from me | Reversible |
|---|---|---|---|---|---|---|---|
| E-01 | Shared Lucide icon foundation; all Blade surfaces | 39 workspace component call sites, 22 independent SVG elements, search glyphs, and notification initials are inconsistent. Replace icon shapes with one static Blade map and migrate every surface, including JS-rendered search/notification items using static Blade templates. Add `docs/ICONS.md` and license notice. | Single icon source; no layout change. | L / medium (broad surface migration) | Lucide A, pinned SVG catalog | Confirm option A and 2.0 stroke | Yes; restore previous call sites/map and remove notice only with the copied SVGs |
| E-02 | Vite and Motion foundation; public/workspace/error layouts | The Vite-built app entry is not loaded by layouts, and the partial motion script uses no Motion API. Wire the deferred Vite module where needed; replace hidden CSS state with visible-by-default, reduced-motion-safe Motion helpers. | Declarative `data-motion`; no default hidden content. | M / medium (layout loading/CSP/build integration) | E-01 is independent; Motion `14.3.0` | Approve Motion bundle ceiling and host build/deploy path | Yes; remove Vite entry inclusion/module hooks |
| E-03 | Below-fold reveals and bounded stagger; public/workspace feeds and lower dashboard lists | There is no authored Motion effect yet. Use reveal for only below-fold content and a capped stagger for one related list; keep all above-fold content static. | Existing content cards remain in place; only transform/opacity change. | M / low | E-02 | Confirm effects are allowed on phones (default: yes, subtle only) | Yes; remove attributes and initializer |
| E-04 | Icon-only control affordance; public/workspace header, search, account and notification controls | Labels exist in many places, but visible tooltip treatment is not consistent. Ensure each icon-only button/link retains its accessible name, has a visible hover/focus tooltip, and meets 44×44px target size without changing its destination/action. | Tooltip appears beside the existing control; no layout reflow. | M / medium (small controls may need spacing) | E-01 | None; required by the original icon-system requirements | Yes; remove tooltip attributes/styles |
| E-05 | Text-paired status icons; public/workspace status labels | Approval/archived meanings currently rely on color/text in several surfaces. Pair mapped icons with the existing status text, never icon alone. | Inline icon + existing label; no copy/state changes. | M / low | E-01 | Approve whether status labels should gain icons | Yes; remove icons only |
| E-06 | Notification badge pop; signed-in public/workspace header | The unread count updates dynamically without a distinct count-change acknowledgement. Add a brief pop only when the count changes, respecting reduced motion and interruption. | Existing badge only; no count behavior change. | S / low | E-02 | Approve notification badge effect | Yes; remove hook |
| E-07 | Count-up stats; dashboards | The user suggested stat count-up, but source values are real server-provided counts. Keep values static by default; if chosen, animate text only after intersecting and preserve the true value on interruption/failure. | No layout change; HTML keeps real number from first paint. | M / medium (announcements and cancellation) | E-02 | Explicit yes/no; default no | Yes; remove count-up hook |
| E-08 | Reading-progress bar; long public articles | Shared reading width already addresses the measured reading-width issue. A progress bar is optional polish, not a current defect, and would add scroll JS. | Thin token-colored line at the existing sticky header; no copy change. | M / low | None | Explicit yes/no; default no | Yes; remove script and bar |
| E-09 | Replace inline flash messages with toasts | No evidence in the scoped audit requires replacing the existing page-level feedback. A toast changes visibility and dismissal behavior; do not do it by default. | Toast region and dismiss affordance would be a new interaction. | L / high | Design and accessibility review | Explicit yes/no; default reject | Yes, but requires restoring inline presentation/announcements |

### Package choices

- **Foundation only:** E-01 through E-02, plus E-04 because labels/tooltips/target size are part of the requested icon system. No motion effect is activated until selected.
- **Recommended set:** Foundation plus E-03 and E-06. It uses Motion for below-fold context and a real count-change state, without changing content or adding a new workflow.
- **Everything:** Recommended set plus E-05, E-07, and E-08. E-09 remains excluded unless separately approved because toast replacement changes feedback behavior.

### Quick wins

- Keep decorative icons inline in the server-rendered HTML: no icon font, CDN, or first-paint request.
- Reuse a single icon component and existing workspace/public CSS tokens; maintain role checks, exact wording, and routes.
- Keep the first viewport static, existing CSS hover/focus/drawer transitions unchanged, and reduce motion to an instant final state.
- Keep existing reading width, skeletons, table scroll/pinned columns, and back-to-top control rather than duplicating them.

### Signature moves

- **Public reading/feed:** restrained below-fold one-time reveal on a small, capped set of feed cards; preserve the existing SSITE blue/navy identity and reading width.
- **Workspace:** static above-fold status cards; a bounded stagger only for a related lower-page list; no new card scaffold or different palette.
- **Notifications/search:** Lucide type/status glyphs cloned from server-rendered templates; current search endpoint and notification actions remain unchanged.
- **Shared shell/errors/forms:** consistent icon scale and accessible icon-only tooltips; no new branded imagery or copy.

### Not recommended

- Runtime icon bundles, icon fonts, a CDN, duplicate icon families, or database/request-selected SVG names: contradict offline performance/security and the one-source requirement.
- Hiding any content in CSS before JavaScript, animating above-fold content, long/repeating motion, animating layout dimensions, or Motion-based menu/drawer replacement when CSS already handles the state.
- Count-up by default, toast replacement, reading-progress scroll tracking, parallax, page transitions, scroll-jacking, autoplay, and cursor effects: not needed for current task completion or introduce extra accessibility/performance risk.
- New backend features (notification schema, analytics, read state, roles, endpoints) are **Optional later** and would require a separate product/security decision.

## 8. Questions and defaults

1. **Lucide route:** Option A inline SVG (recommended/default); Option B requires separate Packagist research and approval. **Default: A.**
2. **Icon stroke:** 2.0 (default) or 1.75. **Default: 2.0**, matching Lucide’s standard and current user-requested range.
3. **Effects and extras:** allow subtle below-fold reveals/staggers on phones (default yes); notification badge pop (default yes in Recommended); count-up (default no); reading progress (default no); toast replacement (default no). Add `THIRD_PARTY_NOTICES.md` (required when copied ISC/MIT SVGs are retained; default yes).

## 9. Risks and implementation order

| Risk | Mitigation |
|---|---|
| Vite is not currently included in the layouts; adding it may affect all routes. | Add only the deferred app JS entry in layouts that actually render; keep CSS delivery unchanged; verify build/manifest and empty route diff. |
| Existing partial CSS hides marked content and would fail without JS if it becomes linked. | Remove the zero-opacity default before enabling the Vite entry; Motion never starts from a CSS-hidden state. |
| Motion transforms create stacking contexts around popover or sticky UI. | Animate only below-fold cards and the badge; never animate shell, dropdown, drawer, modal, or sticky navigation containers. |
| Reduced-motion preference changes while running. | Track Motion controls; stop active work and settle to the final visible state when the media query changes. |
| Release uses school/host network or has no Node. | No CDN; build here from the lockfile and deploy generated `public/build/` files with `manifest.json`. |
| License notice obligations. | Add ISC and applicable Feather-derived MIT notices before shipping extracted SVG paths. |
| Authenticated role HTML cannot be browser-verified here. | Preserve all `@can`/capability blocks and existing control text/destinations; verify source boundaries, route diff, and request supplied role screenshots for visual inspection. |
| Dense selectors or too many cards. | Process only first 12 eligible targets; skip offscreen work; no global reveal. |

**Build order (approved batches still require a separate “go” before each):**

1. Finish the dependency batch: remove temporary `lucide-static` after shape extraction; retain the exact Motion pin and lockfile; record audit, license, build and bundle-size changes in progress.
2. Add one Lucide component, `docs/ICONS.md`, and `THIRD_PARTY_NOTICES.md`; migrate shared shell/public controls and dynamic search/notification templates.
3. Migrate remaining module, error, attachment, and workspace icons by area; verify no old UI SVG/glyph/icon font remains (brand marks are documented exceptions).
4. Replace the incomplete motion attempt with the tested Motion vanilla module, Vite wiring, fallback and reduced-motion behavior.
5. Add only approved effects, one group at a time; no count-up/toast/progress unless explicitly selected.
6. Update `docs/SYSTEM_OVERVIEW.md` and `docs/WHERE_IS_WHAT.md` in place; complete audits, build/size, Blade compile, route diff, and the role/UI limitations report.

For every approved batch, record status, Impeccable command(s), files, before/after metrics, and a dated log in `docs/ICONS_MOTION_PROGRESS.md`. Use the available Impeccable CLI detector before/after; the CLI currently has no direct `critique`/`audit` command. Do not claim browser checks until the app can be served rather than the XAMPP welcome page. Do not run test/database-refreshing commands without first confirming the configured test database is not `ssitecms`.

## 10. Expected final verification

- Route list diff: **empty** (baseline 113 entries).
- Vite build passes; record before/after JS and CSS byte and gzip sizes. Motion incremental JS gzip must remain below 25 kB; otherwise reduce imports/effects or stop for approval.
- `php artisan view:clear`, `view:cache`, then `view:clear` after approved Blade batches; these are compile/cache checks, not database tests.
- Review 320, 360, 768, 1024, and 1440px; keyboard-only; 200% zoom; empty/long content; missing image; reduced motion on and preference toggled while open; JS disabled; Motion entry blocked. If browser access remains unavailable, report each as unverified rather than infer.
- Verify first viewport never animates in, all content remains visible without JavaScript, icons remain inline, no layout shift is introduced, phone motion is brief and nonessential, and all text states remain visible.
- Verify role conditions and server-rendered controls remain unchanged in source; no routes, controller authorization, ownership, upload/download paths, search/notification endpoints, or data change.
- Confirm only one SVG icon-shape source remains; identify justified brand image exceptions and non-icon punctuation separately.
- No login accounts, database queries/writes, migrations, seeders, or test suites are part of this plan.

## 11. Approval table

Reply with a package selection and any overrides, for example: **“Approve the Recommended set; Lucide A; 2.0 stroke; no count-up; no reading progress; allow subtle motion on phones.”**

| ID | Title | Intensity | Effort | Risk | Decision |
|---|---|---|---|---|---|
| E-01 | Shared Lucide icon system and complete replacement map | CONFIDENT | L | Medium | |
| E-02 | Vite wiring and resilient Motion module | SUBTLE | M | Medium | |
| E-03 | Capped below-fold reveals and stagger | SUBTLE | M | Low | |
| E-04 | Visible tooltips and 44px icon-control targets | CONFIDENT | M | Medium | |
| E-05 | Text-paired status icons | SUBTLE | M | Low | |
| E-06 | Notification badge count-change pop | SUBTLE | S | Low | |
| E-07 | Count-up stats | SUBTLE | M | Medium | Approved; complete |
| E-08 | Article reading-progress bar | SUBTLE | M | Low | |
| E-09 | Toast replacement for inline flash messages | CONFIDENT | L | High | |
