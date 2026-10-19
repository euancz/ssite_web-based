# View-only PDF progress

## Batch A — remove download feature

Status: **Complete** (2026-10-19)

Files changed:

- `routes/features.php`: removed the two download endpoints; retained both authorized view routes.
- `app/Http/Controllers/DocumentController.php` and `app/Http/Controllers/LiquidationController.php`: removed download handlers and attachment response branches; retained inline PDF response and upload/replacement filename sanitization.
- `app/Policies/DocumentPolicy.php` and `app/Policies/LiquidationPolicy.php`: removed download abilities.
- `resources/views/components/posts/attachment.blade.php`, `resources/views/liquidation/index.blade.php`, `resources/views/liquidation/show.blade.php`, and `resources/views/workspace/posts/liquidation/index.blade.php`: removed download controls and changed the guest message to “Log in to view.” Liquidation file actions now say “View.”
- `resources/views/components/icon.blade.php` and `public/css/liquidation.css`: removed the unused download glyph and its button styling.
- `tests/Unit/ContentAuthorizationTest.php`: retained coverage of President/student `viewFile` access without a download ability assertion.
- `docs/ICONS.md`, `docs/ICONS_MOTION_PLAN.md`, `docs/FEED_DESIGN.md`, `docs/DESIGN_SYSTEM.md`, `docs/SYSTEM_OVERVIEW.md`, and `docs/WHERE_IS_WHAT.md`: removed current download terminology and updated the descriptions to view-only file access.

Before / after metrics:

| Measure | Before | After |
| --- | ---: | ---: |
| Registered routes | 113 | 111 |
| Download-named routes | 2 | 0 |
| JS bundle | 122,715 bytes / 44,923 gzip | 122,715 bytes / 44,923 gzip |
| Existing CSS artifact | 34,403 bytes / 6,761 gzip | Fresh build: 14,284 bytes / 3,612 gzip |

The pre-batch CSS artifact did not match the current fresh Vite build, so its apparent size reduction is not attributable to this batch. No dependency changed; no lazy viewer chunk exists yet.

Verification:

- `php artisan view:clear`, `php artisan view:cache`, then `php artisan view:clear`: passed.
- `npm run build`: passed. Main JS size is unchanged; no PDF.js chunk exists yet.
- `php artisan route:list --json`: 111 routes; only `documents.view` and `liquidations.view` remain for PDF delivery, both with `web`, `auth`, and `profile.complete` middleware. Download route names are absent. The old suffixed paths no longer have registered routes and therefore use the framework's 404 handling.
- `php artisan test --filter=ContentAuthorizationTest`: 4 passed, 1 failed. The failure is the existing `pending rejected and archived posts are not publicly visible` assertion at line 82: it expects an approved archived Article to be hidden, while `ArticlePolicy::view()` and the system overview explicitly allow approved archived record pages. This is outside Batch A and was not changed.
- Source scan confirms no download route names, controller methods, policy abilities, or rendered download control in `app`, `routes`, `resources`, or `tests`. The generic navigation click guard for links carrying an HTML `download` attribute remains in `resources/views/layouts/app.blade.php`; Axios also includes generic `onDownloadProgress` transport support in the compiled bundle. Neither creates a PDF action or URL. Historical route/audit snapshots and this plan retain references as before-state evidence.
- `git diff --check`: passed.

Issues found: (1) the generated pre-batch CSS artifact was stale relative to a fresh Vite build; (2) the authorization test expectation for approved archived Article pages conflicts with the existing policy and system documentation. Neither issue was changed in this batch.

Log: 2026-10-19 — removed the download feature surface and aligned current docs; upload and replace paths remain intact. Waiting for approval before Batch B dependency metadata/install work.

## Batch B — package metadata review (pre-install gate)

Status: **Registry metadata reviewed; revised candidate awaiting approval; install not started** (2026-10-19)

Commands approved and run:

- `npm view pdfjs-dist@4.10.38 version time.4.10.38 license scripts maintainers dist.unpackedSize`
- `npm view pdfjs-dist@4.10.38 readme`

The sandbox attempts waited without returning data; the same approved commands succeeded through the required network escalation. Metadata returned version `4.10.38`, license `Apache-2.0`, no lifecycle scripts, maintainers `yurydelendik`, `pdfjsbot`, and `brendandahl`, and unpacked package size 37,350,025 bytes. The published README describes the package as a prebuilt PDF.js distribution and points older environments to `legacy/`.

The follow-up query `npm view pdfjs-dist time --json` returned the release timestamp: `4.10.38` was published 2025-01-01, so it meets the 14-day minimum. The registry also lists newer releases, including `6.3.289` from 2026-08-29 and `6.4.299` from 2026-10-03. Because the planned candidate is substantially older than available releases, I am holding installation to ask whether to keep `4.10.38` or verify the newer, more mature `6.3.289` as the candidate. The README response did not include API usage. No dependency or lockfile changed, no scripts ran, and no package audit or bundle measurement was performed. The installed package README/docs must still be read before use.

Before / after metrics: dependency count and package files unchanged; current production JS remains 122,715 bytes / 44,923 gzip. No lazy viewer chunk exists.

The user approved and the following newer-candidate queries were run:

- `npm view pdfjs-dist@6.3.289 version license scripts maintainers dist.unpackedSize`
- `npm view pdfjs-dist@6.3.289 readme`

They confirm `6.3.289` is Apache-2.0, has no lifecycle scripts, was published 2026-08-29 (51 days before this plan date), and is 34,781,083 bytes unpacked. Five maintainers are listed: `yurydelendik`, `pdfjsbot`, `brendandahl`, `calixteman`, and `pdfjs-release-automation`. I revised the recommendation in `docs/VIEW_ONLY_PDF_PLAN.md` from `4.10.38` to `6.3.289`; the older candidate remains documented above as the initial proposal.

Next: approve the revised `6.3.289` candidate and the exact install/audit commands before dependency mutation or further registry access.

Log: 2026-10-19 — verified the original candidate's release age, reviewed the newer candidate, and revised the plan recommendation; installation remains held for approval.

## Batch B — exact PDF.js dependency

Status: **Complete** (2026-10-19)

Files changed:

- `package.json`: added only the exact dependency `"pdfjs-dist": "6.3.289"`.
- `package-lock.json`: recorded PDF.js and its resolved optional platform packages.
- `THIRD_PARTY_NOTICES.md`: added the full Apache-2.0 license and the license texts supplied for PDF.js supporting CMap, ICC, standard-font, and WASM assets.

Install and audit:

- `npm install --save-exact --ignore-scripts pdfjs-dist@6.3.289`: succeeded; 3 packages added. The initial sandbox attempt stalled, and the same approved command succeeded with network escalation. The installed package reports `scripts: {}`; scripts were disabled regardless.
- `npm audit`: passed; 0 vulnerabilities.
- `package.json` and `package-lock.json` both pin `6.3.289` exactly. The local Node runtime is v24.15.0, meeting the package's declared Node engine range.
- Package README in `node_modules/pdfjs-dist/README.md` describes the prebuilt distribution and its `legacy/` directory but has no usage example. The package's own declarations confirm `getDocument` is exported, `GlobalWorkerOptions.workerSrc` should be set, and `PDFPageProxy.render({ canvasContext, canvas, viewport })` returns a `RenderTask`. The package provides `build/pdf.mjs` (860,384 bytes) and `build/pdf.worker.mjs` (2,228,489 bytes); these will be used as Vite-bundled assets in the viewer batch.

Before / after production bundle metrics (raw / gzip):

| Asset | Before dependency | After dependency, not yet imported |
| --- | ---: | ---: |
| Main JS | 122,715 / 44,923 bytes | 122,715 / 44,923 bytes |
| Main CSS | 14,284 / 3,612 bytes | 14,311 / 3,622 bytes |
| Lazy PDF.js chunk | Not present | Not present |

The JS bundle is unchanged. CSS differs by 27 raw bytes / 10 gzip bytes between fresh builds, even though PDF.js is not imported; no PDF.js code enters the current bundle. Lazy chunk size will be recorded after the viewer is implemented and built.

Verification: the install completed with `--ignore-scripts`; explicit `npm audit` reported 0 vulnerabilities; `npm run build` passed; `git diff --check` passed.

Issues found: no package vulnerability was reported. The package README itself does not document the API; API usage was verified from the installed package's own declaration files as described above.

Log: 2026-10-19 — installed exact PDF.js version 6.3.289 with scripts disabled, audited dependencies, added license notices, and measured the unchanged main JS bundle. Waiting for approval before Batch C serving changes.

## Batch C - private PDF delivery hardening

Status: **Complete** (2026-10-19)

Changed:

- Added `app/Support/PrivatePdfResponse.php` for inline streaming with single byte-range support, correct `206` and `416` responses, sanitized `Content-Disposition`, `Accept-Ranges`, `private, no-store`, `nosniff`, and `X-Robots-Tag: noindex` headers.
- Both controllers now mint a five-minute temporary signed URL only for a signed-in actor allowed by the existing `viewFile` policy. Each file request rechecks policy, signature, private path, and file existence; denied, invalid, expired, and missing-file requests return 404.
- Each request applies the best-effort `Sec-Fetch-Dest: document` rejection. Requests without that header remain supported; PDF.js fetch requests will use the range endpoint in the next batch.
- Feed and edit links now lead to the record page, where the short-lived URL is issued. Existing `.view` route names, URIs, and middleware remain unchanged. Registered route count remains 111 (113 baseline minus the two removed download routes).
- Added `tests/Unit/PrivatePdfResponseTest.php` for partial-body/header and unsatisfiable-range behavior.

Verification:

- `php -l` passed for both controllers, the response helper, and the focused test.
- `php artisan test --filter=PrivatePdfResponseTest`: 2 passed, 9 assertions.
- `php artisan view:clear`, `php artisan view:cache`, and `php artisan view:clear` passed.
- `npm run build` passed; main JS remains 122,715 bytes / 44,923 gzip, and main CSS is 14,311 bytes / 3,622 gzip. No viewer chunk is expected before Batch D.
- `php artisan route:list`: 111 registered routes; `documents.view` and `liquidations.view` retain their existing paths and names.
- `git diff --check` passed. Source search found no direct view-route links in Blade; all current entry links lead to show pages.

Limitations: Live role walkthrough, signature-expiry request, and deployed browser `Sec-Fetch-Dest` behavior require the Batch D viewer and an authenticated app session; real-phone verification remains in the final checklist. The earlier `ContentAuthorizationTest` failure for archived Article visibility remains unrelated and unchanged.

Log: 2026-10-19 - implemented signed private delivery, range streaming and response headers; passed focused response tests, route checks, Blade cache and production build. Waiting for approval before Batch D viewer work.

## Batch D - Documents canvas viewer

Status: **Complete** (2026-10-19)

Changed:

- `resources/views/documents/show.blade.php` now contains a viewer stage below the document details. It includes page status, Previous/Next, zoom in/out, fit-width, a retry/reload state, and a no-JavaScript message. The record card's View link jumps to the viewer instead of navigating to the PDF endpoint.
- `resources/js/app.js` checks for the Documents viewer before dynamically importing it. `resources/js/document-pdf-viewer.js` loads PDF.js and its locally bundled worker only for that page, then renders the active page to one canvas. No PDF text layer is created.
- `resources/css/document-pdf-viewer.css` styles the reader with existing SSITE colors and button patterns, visible focus, a neutral stage, and wrapping controls for narrow screens.
- The viewer starts fit-to-width, handles page/zoom changes and resizing, announces page/status changes, and exposes a reload action after load/render errors. Text remains non-selectable because pages are canvas-rendered.
- The shared attachment component now scrolls authorized Documents users to the in-page viewer rather than opening the signed endpoint as a top-level PDF navigation.

Verification:

- `npm run build` passed. The manifest confirms `document-pdf-viewer.js` is a dynamic entry and the PDF worker is a separate local asset.
- Bundle sizes at the Batch D build (raw / gzip): main JS 124,509 / 45,749 bytes; lazy PDF.js JS 484,796 / 144,938 bytes; worker 2,228,489 / 476,845 bytes; lazy viewer CSS 2,294 / 850 bytes. That build overlapped Blade view caching, so its main CSS measurement was not a clean baseline.
- Main JS contains only the small dynamic import bootstrap; the PDF.js code and worker are fetched lazily. No CDN reference was added.
- `php artisan view:clear`, `php artisan view:cache`, `php artisan view:clear` passed. `php artisan test --filter=PrivatePdfResponseTest` passed (2 tests, 9 assertions). `git diff --check` passed.
- Responsive source styles wrap controls below 480px and keep the canvas in a horizontally scrollable stage when zoomed. Browser screenshots at 320px, 768px, 1440px and 200% zoom were not available in this environment; test those in the next visual review.

Measurement correction: the main CSS appeared as 34,430 bytes / 6,767 gzip because Vite ran while `storage/framework/views` contained compiled Blade views, which Tailwind scans. A sequential clean rebuild after `view:clear` restored the main CSS to 14,311 / 3,622, matching Batch C.

Log: 2026-10-19 - added the Documents-only lazy canvas viewer, local PDF.js worker, responsive controls, and error/no-JS states; production build and focused delivery test passed. Waiting for approval before Batch E.

## Batch E - Liquidation viewer and resilience

Status: **Complete** (2026-10-19)

Changed:

- `resources/views/liquidation/show.blade.php` now uses the same lazy canvas viewer and links the View action to its in-page preview. Documents and Liquidation viewer elements share one initializer.
- `resources/js/document-pdf-viewer.js` now reports coarse loading progress, identifies common expired/access-denied responses, supports reload recovery, preserves fit width on resize/orientation changes, handles arrow/page and +/-/0 zoom keys, and provides native fullscreen with a fixed-position fallback and focus return.
- Canvas backing dimensions are capped at 4096px per edge and about 16 million pixels, with DPR capped at 2. Only one page canvas is retained, keeping page changes within the same memory bound.
- Both viewer roots expose keyboard help to assistive technology. Fullscreen state is announced through the button's pressed state.
- Corrected the Batch D CSS measurement: the earlier larger app CSS bundle came from overlapping `npm run build` with `view:cache`, since Tailwind scans compiled Blade views. Rebuilding sequentially after `view:clear` restored the main CSS to the Batch C size.

Verification:

- Sequential `npm run build` passed after clearing compiled views. Manifest confirms both show pages use a shared dynamic viewer import and the PDF worker remains a separately emitted local asset.
- Current asset sizes (raw / gzip): main JS 124,535 / 45,754 bytes; lazy viewer JS 486,805 / 145,647 bytes; worker 2,228,489 / 476,845 bytes; main CSS 14,311 / 3,622 bytes; lazy viewer CSS 2,875 / 994 bytes.
- `php artisan view:clear`, `php artisan view:cache`, and `php artisan view:clear` passed. `php artisan test --filter=PrivatePdfResponseTest` passed (2 tests, 9 assertions). Controller PHP syntax checks and `git diff --check` passed.
- No browser automation or authenticated browser session is available here, so fullscreen and rotated-device behavior are implemented and build-checked but not screenshot-tested on a phone. Real-device review remains needed.

Log: 2026-10-19 - extended the shared viewer to Liquidation and added bounded canvas memory, resize/orientation handling, keyboard controls, fullscreen fallback, and loading/expiry recovery; build and focused delivery checks passed. Waiting for approval before Batch F.

## Batch F - Canvas deterrents, print notice, and reduced motion

Status: **Complete** (2026-10-19)

Changed:

- Added canvas-only context-menu and drag deterrents, disabled canvas dragging, and scoped selection/touch-callout styling to the rendered canvas. These are casual-copy speed bumps only; authorized browsers still receive the PDF bytes.
- Added a temporary print notice through `beforeprint`/`afterprint`. Print CSS omits the PDF canvas and viewer controls while preserving the surrounding record details and notice.
- Added scoped reduced-motion handling for the viewer. Existing no-JavaScript messages remain in both viewer pages.

Verification:

- `php artisan view:clear`, `php artisan view:cache`, and `php artisan view:clear` passed.
- Sequential `npm run build` passed after clearing compiled views. Current sizes (raw / gzip): main JS 124,535 / 45,754 bytes; lazy viewer JS 487,343 / 145,811 bytes; worker 2,228,489 / 476,845 bytes; main CSS 14,311 / 3,622 bytes; lazy viewer CSS 3,764 / 1,201 bytes.
- `php artisan test --filter=PrivatePdfResponseTest` passed (2 tests, 9 assertions); `git diff --check` passed. Browser print preview and interaction screenshots are not available in this environment.

Log: 2026-10-19 - added scoped canvas-only interaction deterrents, print warning behavior, print CSS, and reduced-motion handling; build, Blade compilation, focused delivery tests, and whitespace checks passed. Waiting for approval before Batch G.

## Batch G - Final polish and documentation

Status: **Complete** (2026-10-19)

Changed:

- Replaced the PDF zoom controls' text glyphs with the shared SVG icon component and added its matching minus icon, keeping the established button styling and accessible labels.
- Updated `docs/SYSTEM_OVERVIEW.md` with the lazy PDF.js canvas flow, private signed delivery, print behavior, and the limits of canvas deterrents.
- Updated `docs/WHERE_IS_WHAT.md` with the viewer implementation locations.

Final verification:

- `impeccable detect --json` reported no findings for the viewer styles, script, and affected Blade views.
- `php artisan view:clear`, `php artisan view:cache`, and `php artisan view:clear` passed; sequential `npm run build` passed.
- `php artisan test --filter=PrivatePdfResponseTest` passed (2 tests, 9 assertions); `php artisan route:list --json` reports 111 routes, with no download routes; `git diff --check` passed.
- Final built assets (raw / gzip): main JS 124,535 / 45,754 bytes; lazy viewer JS 487,343 / 145,811 bytes; worker 2,228,489 / 476,845 bytes; main CSS 14,311 / 3,622 bytes; lazy viewer CSS 3,764 / 1,201 bytes. No browser automation or authenticated session is available for visual or print-preview testing.

Log: 2026-10-19 - completed the viewer control polish, system documentation, and final authorized checks. View-only PDF work is complete.

## Follow-up - PDF viewer modal

Status: **Complete** (2026-10-19)

- Wrapped the authorized Documents and Liquidation viewers in native `<dialog>` modals. View buttons open the matching dialog; close button, Escape, backdrop click, and native focus return are supported.
- PDF.js remains lazy and now loads only after opening the dialog, so rendering starts with the modal's measured size. View controls remain gated by `viewFile`; server authorization and signed delivery are unchanged.
- Styled the modal for desktop and full-screen phone layouts, and updated the existing attachment/action button styles.
- Verification passed: Impeccable detector reported no findings; Blade clear/cache/clear, production build, focused private PDF response tests (2 tests, 9 assertions), 111-route inventory, and `git diff --check`. Browser interaction screenshots were unavailable.
