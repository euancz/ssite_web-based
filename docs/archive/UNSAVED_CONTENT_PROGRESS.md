# Unsaved content protection

Implemented October 27, 2026. No migration or application data changes.

The shared article, activity, achievement, document, and liquidation create/edit forms now show “You have unsaved changes.” after text, metadata, or attachment changes. Returning controls to their original values clears the status and removes the navigation listener. Responses with validation errors start protected because the submitted changes have not been saved.

The native `beforeunload` warning is registered only while needed. Normal submit and Save draft actions disarm it; invalid submissions retain it. Canceled submit events restore protection and enabled buttons. Browser `pageshow` restores protection when edited forms return from navigation. Hidden workflow/security fields and action buttons do not count as edited content.

This does not autosave or copy content into local/session storage. Browser-controlled warning dialogs require user interaction and are not guaranteed during mobile app termination or crashes. Drafts still save text and metadata only; selected attachments must be included in a content submission. File changes are compared using name, size, and modification time.

## Verification

- Production Vite build and JavaScript syntax check passed.
- Six existing content-workspace tests passed with 605 assertions. The previous full suite remains 94 tests / 1,362 assertions; it was not rerun for this JavaScript-only batch.
- Isolated SQLite-rendered fixtures covered all five create forms in Chrome at 1440px, 768px, and 320px without horizontal overflow or JavaScript exceptions in the final run.
- Browser checks covered clean/edited/reverted states, file selection and clearing, invalid submissions, canceled submissions, restored submit buttons, and simulated `pageshow`. Synthetic cancelable `beforeunload` events verified registration and removal; native dialog presentation and actual back/forward cache restoration were not automated.
- An actual Save draft button submission reached its expected POST destination through the local fixture server. This tested browser submission behavior, without writing a draft to the application database.
- Local fixtures, screenshots, and results are under `.tmp/unsaved-content/`. The initial fixture run reported a Chrome view-transition opt-in error; matching the POST stub's navigation opt-in to the app and allowing transitions to finish resolved it without changing application motion.
