# Content attachment controls

Implemented October 27, 2026. No migration or new dependency.

Article, activity, achievement, document, and liquidation forms now share selected-file feedback and a Clear selection button. Feedback shows the filename and size as escaped text, wraps long names, and announces changes through a polite status region. Clearing returns focus to the file input, refreshes image previews, and updates unsaved-change protection. It clears only the pending selection; it does not delete an existing attachment.

Images use the existing 2,048 KB limit. PDF limits come from `school.max_pdf_size_kb`, matching the existing Form Requests. Files above the limit receive a native validation error before ordinary submission. Save draft retains its existing validation bypass and saves text/metadata only. MIME, image-byte, and PDF-header checks remain server responsibilities. Browser checks do not establish that a file is safe or acceptable.

`workspace/upload-status.blade.php` renders the progressive-enhancement hooks. `content-uploads.js`, initialized by the shared form script, reads local file metadata and sets browser validity; it does not upload or store files. It replaces duplicate inline PDF filename scripts. Without JavaScript, native file inputs and Laravel validation remain usable. Private PDF storage and view-only controls are unchanged.

## Verification

- Production Vite build and JavaScript syntax check passed.
- 11 existing workspace/persistence tests passed, 639 assertions.
- Chrome checked all five create forms at 1440px, 768px, and 320px using isolated SQLite-rendered fixtures. Exact-limit files, oversized files, clear selection, focus restoration, safe filename text, image preview cleanup, and unsaved-state integration passed. Long selected filenames caused no horizontal page overflow. No JavaScript exceptions occurred.
- A Save draft click reached the local fixture POST endpoint; no application database writes were made. Full live uploads and actual edit-page replacement uploads were not exercised in this browser batch.
- Local fixture script and results: `.tmp/content-uploads/`.
