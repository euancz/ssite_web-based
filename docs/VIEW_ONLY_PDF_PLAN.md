# View-only PDF plan

## Phase 0 preflight

- Working tree was clean before this plan; no `docs/VIEW_ONLY_PDF_PROGRESS.md` exists, so this is a new effort.
- `docs/ROUTES_BEFORE.txt` already contains the route snapshot. It shows 113 registered routes, including `documents.download` (`GET documents/{document}/download`) and `liquidations.download` (`GET liquidation/{liquidation}/download`). It will be the baseline; no application route was changed.
- Source audit found two download routes, two `download` policy abilities, and four rendered download-control locations: shared post attachment (Documents and Liquidation feed/show/workspace cards), public Liquidation index and show, and workspace Liquidation index. The shared attachment is one template location. Matching download-specific authorization assertions are in `tests/Unit/ContentAuthorizationTest.php`.
- Additional stale wording exists in `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/FEED_DESIGN.md`, `docs/DESIGN_SYSTEM.md`, `docs/ICONS.md`, `docs/ICONS_MOTION_PLAN.md`, older planning/progress notes, audit snapshots, and the database schema audit snapshot. `README.md` has an unrelated Packagist “Total Downloads” badge. Browser navigation JS checks generic `download` attributes; that behavior is unrelated and should not be removed.
- Existing `public/storage` is a junction to `storage/app/public`; this is distinct from the configured private local disk folders `storage/app/private/documents` and `storage/app/private/liquidation`. The source/config shows no deliberate public PDF URL. This is a read-only structural check, not proof that no manually copied PDF exists under public storage; the build phase will inspect the actual folders and verify an uploaded private file is not reachable via a public path.
- Local PHP has no Imagick module. `magick`, `gs`, and `pdftoppm` were not found on PATH. Server-rendered images are therefore not available in the inspected local setup; host availability remains unknown and would need a read-only host check if that option is selected.
- Current package files contain no `pdfjs-dist` dependency or installed package. Existing production JS is 122,715 bytes raw / 44,923 bytes gzip; CSS is 34,403 bytes raw / 6,761 bytes gzip. These are the committed build artifacts, not a fresh build. The lazy PDF.js chunk and post-change totals cannot be measured until a dependency install and production build are approved.

## Recommendation and viewer critique

Recommend **Option 1: PDF.js canvas viewer**. It keeps the PDF response behind the current authenticated, profile-complete controller route and places rendered pages inside the SSITE page instead of invoking a browser PDF viewer. Option 2 would require conversion software not detected locally, adds server CPU and private image-cache management, and still delivers viewable page images to the browser. Both options have accessibility and capture limits.

The existing Documents card already separates metadata from its file action, which is a good base for a calm reading surface. Liquidation currently has a preview link and a separate download action; both modules should converge on one viewer region and one View entry point. On the show page, keep real HTML title, category or report date, description, page count, file size, and the “View only” note outside the canvas so they remain readable when rendering fails. Keep management actions in their existing policy-gated area. Do not place a large canvas or controls in feed cards.

## Wireframes

Phone (320–767 px; details come before viewer; fit width on entry):

```text
┌────────────────────────────┐
│ Back   Documents           │
│ Title                      │
│ Category · Date            │
│ Description (if available) │
│ View only · 12 pages · 2 MB │
├────────────────────────────┤
│ ‹       Page 3 of 12      ›│
│ [ − ] [Fit width] [ + ] [⛶]│
│                            │
│       rendered page        │
│                            │
└────────────────────────────┘
```

Use 44px minimum controls, wrap controls instead of shrinking hit targets, and keep ordinary vertical page scrolling. Browser-native pinch gestures remain enabled; explicit zoom buttons are the predictable alternative and do not capture all touch gestures. Orientation changes recalculate fit width while preserving the current page. Full-screen uses the Fullscreen API where available and an in-page immersive fallback on iOS Safari; Escape and browser Back leave immersive mode.

Desktop (768–1440 px):

```text
┌──────────────────────────────────────────────────────────────┐
│ SSITE navigation                                             │
│ Documents / title                                            │
│ Description and metadata · View only · 12 pages · 2 MB       │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ ‹ Previous   Page 3 of 12   Next ›  −  100%  +  Fit  ⛶ │ │
│ ├──────────────────────────────────────────────────────────┤ │
│ │                 centered rendered page                   │ │
│ └──────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

The viewer remains within the normal page stacking context when not fullscreen. Fullscreen must account for the site navigation stacking order and restore focus to its toggle on exit.

## Rendering and accessibility decisions

- Load a small viewer bootstrap on show pages only, then dynamically import the viewer and PDF.js. Feed and index pages do not import the viewer. Bundle the worker from the installed package through Vite; no CDN.
- Render only the visible page and its immediate neighbors; maintain at most three page canvases and release farther canvases. Cap DPR at 2, canvas edge at 4096 pixels, and canvas area at 16 million pixels. These conservative caps bound memory on phones but can soften very large pages. A 50+ page file should remain navigable because pages are rendered on demand; the current configured upload ceiling is 10 MB, while page complexity can still make any individual page expensive. If allocation fails, stop rendering and show a recovery message while preserving the page details and navigation.
- Default mobile zoom is fit-to-width. Desktop starts fit-to-width as well. Previous/next, zoom +/- and fit-width controls have labels, tooltips, visible focus, keyboard arrows / +/- / Escape, and reduced-motion-safe instant updates. Fullscreen must preserve page index and return focus.
- Selectable text layer: **off by default, awaiting user choice**. This blocks ordinary text selection in the viewer and means screen readers cannot read PDF page text. Alternative: put a useful text summary in the existing description, with title, category, page count, and date as normal HTML. This does not make screenshots or developer-tool capture impossible.
- Include a `<noscript>` detail block saying “Enable JavaScript to view this document”; never use an iframe, embed, object, native PDF fallback, or file link. If the lazy chunk fails, show a friendly inline retry/error message.
- Honest limit for docs and code comments: the browser receives PDF bytes to render. A determined person can capture them with developer tools or screenshots. This removes the download feature and makes casual saving harder; it does not make content impossible to copy. For truly confidential material, publish a summary instead of the full file.

## Serving design

- Keep the existing named `documents.view` and `liquidations.view` endpoint paths, but make each an inline-only response for the viewer. Apply the current `auth` and `profile.complete` middleware and authorize `viewFile` on **every initial and range request**. Keep policy behavior: active approved content is available as before; the President and the item's owning posting officer can access their non-public item; everyone else receives 404. Guests retain the current login redirect. Pending, rejected, and archived items still require the same current policy eligibility. No paths or storage URLs enter HTML.
- Return `Content-Type: application/pdf`, sanitized `Content-Disposition: inline; filename="…"`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`, and `X-Robots-Tag: noindex`. A missing file should produce a non-path 404 response that the viewer turns into a friendly message; keep the post record manageable.
- Recommended short-lived signed URL: yes, five minutes, generated for the logged-in viewer from the show page. Signature supplements session authentication and policy checks, and must be checked again on range requests. If it expires while the page is open, refresh the URL once or show “Reload to continue.” It is not a substitute for authorization.
- Recommended `Sec-Fetch-Dest` check: yes, best effort. When present, reject top-level `document` navigation to the PDF route with 404; do not reject `empty` fetches from PDF.js. Older clients may omit this header and non-browser clients can spoof it, so auth, signature, and policy remain authoritative.
- Support byte ranges because PDF.js can request partial data. Validate range syntax and bounds, return 206 with `Content-Range`, `Accept-Ranges: bytes`, and `Content-Length`; reject unsatisfiable ranges with 416. Re-run auth, profile, signature, and policy checks for every request. Ensure the storage layer streams the requested segment without copying the complete PDF into memory. Full response and range response both retain the private/no-store and nosniff headers.
- Removing the old download routes means old URLs return Laravel's default 404. No redirect is planned unless the user chooses it.

## Package candidate and approval-gated measurements

- Selected and installed: `pdfjs-dist@6.3.289`, exact version, published 2026-08-29 (51 days before this plan's date). The registry and installed package report Apache-2.0, no lifecycle scripts, five maintainers (`yurydelendik`, `pdfjsbot`, `brendandahl`, `calixteman`, `pdfjs-release-automation`), and 34,781,083 unpacked bytes. The package README identifies the prebuilt PDF.js distribution and points older environments to `legacy/`; its installed type declarations confirm the `getDocument`, `GlobalWorkerOptions.workerSrc`, and page `render` APIs. A newer 6.4.299 release exists from 2026-10-03; 6.3.289 was selected for a larger age buffer. The license text and supporting asset notices are recorded in `THIRD_PARTY_NOTICES.md`.
- Existing baseline from current generated artifacts: application JS 122,715 bytes / 44,923 gzip; CSS 34,403 bytes / 6,761 gzip. Lazy chunk size and new main bundle size are pending the approved build. Report both raw and gzip figures separately.
- Exact package commands approved and run in Batch B (shown here as the audit trail):

```powershell
npm view pdfjs-dist@6.3.289 version license scripts maintainers dist.unpackedSize
npm view pdfjs-dist@6.3.289 readme
npm install --save-exact --ignore-scripts pdfjs-dist@6.3.289
npm audit
```

The two `npm view` commands are network queries; install and audit may also access the registry. Each exact network command required the user's approval before it ran. Installation used `--ignore-scripts`, and `package.json` contains the exact version without a range. The lockfile, audit, license, release age, lifecycle scripts, maintainers, README, and API declarations were reviewed before the viewer work. If implementation evidence contradicts the pinned API, stop and revise the plan for approval rather than silently substituting a version.

## Impeccable design-system direction

Treat the viewer as an Operate surface with a Read task: compact controls, clear page status, stable reading width, existing SSITE Poppins and blue/navy tokens, Lucide `<x-icon>` controls, existing focus-ring and button patterns. Use subdued separators and a neutral page stage so document content leads. No new color system or decorative animation. Honor reduced motion. Verify 320px, 768px, and 1440px layouts and 200% zoom in the build batches.

## Expected route-list diff

Initial removal-only batch: remove exactly two rows, `documents.download` and `liquidations.download` (expected registered route count 113 → 111). Keep both existing `.view` routes and their middleware/names. Viewer transport and signed URL design uses the same route names; no route additions are expected. If implementation requires a separate signing or range route, update this prediction and obtain approval before that batch.

## Files expected to change

Expected in the full approved build, grouped by batch:

- Removal: `routes/features.php`; `app/Http/Controllers/DocumentController.php`; `app/Http/Controllers/LiquidationController.php`; `app/Policies/DocumentPolicy.php`; `app/Policies/LiquidationPolicy.php`; `tests/Unit/ContentAuthorizationTest.php`; `resources/views/components/posts/attachment.blade.php`; `resources/views/documents/show.blade.php`; `resources/views/liquidation/{index,show}.blade.php`; `resources/views/workspace/posts/liquidation/index.blade.php`; associated download copy and CSS/JS if the audit finds any; `resources/views/components/icon.blade.php` only if its download glyph is otherwise unused; and scoped docs including `docs/ICONS.md`, `docs/FEED_DESIGN.md`, `docs/DESIGN_SYSTEM.md`, `docs/ICONS_MOTION_PLAN.md`, plus progress notes that describe current behavior. `docs/WORKSPACE_PLAN.md`, historical route snapshots, audit artifacts, and unrelated words such as the README badge are evidence/history and should not be rewritten unless they claim current behavior or the final proof requires updating them.
- Dependency/viewer: `package.json`, `package-lock.json`, `resources/js/app.js`, a new viewer module under `resources/js/`, a new viewer stylesheet under `resources/css/` (or existing module stylesheet), `vite.config.js` only if worker asset configuration needs it, `resources/views/documents/show.blade.php`, and `resources/views/liquidation/show.blade.php`.
- Serving hardening: the two module controllers and possibly a small shared PDF-response helper under `app/Support/`; prefer no new route unless required. Comments should explain authorization per request, canvases, text layer choice, and deterrent scope in the project's short comment style; Blade comments use `{{-- --}}`.
- Docs: update `docs/SYSTEM_OVERVIEW.md` and `docs/WHERE_IS_WHAT.md` in place, `docs/ICONS.md`, and add `THIRD_PARTY_NOTICES.md` if required. Add `docs/VIEW_ONLY_PDF_PROGRESS.md` at the start of Phase 2, as explicitly requested.

This is an estimate from the current source audit. Each Phase 2 batch plan will name its precise files before asking for “go”; no out-of-batch edits are included.

## Risks and mitigations

- Low-memory phones and complex pages: three-canvas window, DPR/area caps, catch allocation failures and show a clear retry/reload message.
- iOS canvas limits vary by device and OS; conservative caps reduce risk but must be tested on a real iPhone.
- CSP may block a worker or generated blob URL. Bundle worker locally and test under the deployed CSP; no CDN fallback.
- Fullscreen APIs differ on iOS; provide CSS immersive fallback and test Escape/Back, focus return, orientation, and navigation stacking.
- Large files, slow networks, and expired signatures: lazy chunk, loading state, range requests, bounded retry, then explicit reload state.
- View-only UI cannot prevent deliberate copying. Be precise in UI and docs; do not label this copy protection.
- No-JavaScript users cannot see the PDF by design; retain the plain metadata/message without a native viewer fallback.
- Range and signature interactions add server complexity; authorization and no-store headers must apply on each partial response.

## Build order after approval

1. Batch A: remove download routes, controller methods/abilities, controls, and current-copy references; preserve uploads/replacements. Search the full source and built output for remaining user-facing links, then report historical/audit references separately. Update the progress log.
2. Batch B: after showing and receiving approval for exact npm commands, verify candidate metadata, install exact version with scripts disabled, inspect package documentation and license, review audit and lockfile, measure current/lazy bundle sizes. Update the progress log.
3. Batch C: harden the existing view response with range semantics, signed URL handling, headers, 404 behavior, and best-effort fetch-destination check. Update the progress log.
4. Batch D: implement and lazy-load the PDF.js canvas viewer on Documents only; review desktop/phone states and approved text-layer choice. Update the progress log.
5. Batch E: add Liquidation viewer; then mobile rotation, memory limits, fullscreen fallback, keyboard, errors, and slow/expired-link behavior. Update the progress log.
6. Batch F: add scoped deterrents (viewer-only selection/callout styles, canvas-only context menu, no dragging), print notice with `beforeprint` and print CSS, no-JS message, and reduced-motion handling. Comments must call these deterrents/speed bumps, never protection.
7. Batch G: polish with existing tokens/icons, update the two system docs in place, and final authorized verification. No work from a later batch is bundled into an earlier batch.

Each batch requires a separate short chat plan and the user's “go” before any implementation. After approval, perform only that batch, then verify the approved checks and update the progress log before stopping. Do not add unrelated fixes.

## Decisions requested before Phase 2

Defaults are bolded.

1. Viewer: **PDF.js canvas (recommended)** or server-rendered images?
2. PDF text layer: **off** or on?
3. Old download URLs: **404** or redirect to the show page?
4. Five-minute signed URL: **yes** or no?
5. Best-effort `Sec-Fetch-Dest` check: **yes** or no?
6. Canvas-only context-menu deterrent: **yes** or no?
7. Show page count and file size: **yes** or no?
8. “View only” note on page: **yes**, with wording “View only”, or no?

Optional later: a per-viewer watermark; publishing a summary instead of a full file when material is truly confidential; server-rendered private page images if a supported converter is later confirmed and the tradeoffs are accepted.
