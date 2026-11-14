# Website fixes and verification - 14 November 2026

The findings from `WEBSITE-REVIEW-2026-11-14.md` have been addressed in the application and checked locally. Deployment-specific items below remain explicit limits.

## Changes

- Updated the locked PHP dependencies, including Laravel 12.72.0, phpseclib 4.0.3, and the affected Symfony components. Updated Vite to 6.4.6 and follow-redirects to 1.16.2. Both live dependency audits report zero known vulnerabilities.
- Profile-picture replacement and removal now lock the account, persist the database change before removing the old image, preserve the old image on failed writes, and clean up failed replacements. Upload failures produce validation feedback.
- New article, activity, and achievement images use private storage. Web delivery requires a completed authenticated session; unpublished/archived images require current editor permission. Mobile delivery still requires a bearer token and approved active content.
- Added a resumable legacy image migration with copy checksums, transactionally updated references, and public-copy revocation. Ran it successfully against the configured database/storage: **one legacy attachment privatized**. Confirmed that the configured private Supabase bucket is marked private. No content was deleted.
- Fixed a draft-image persistence bug found during validation: promoted attachment paths are now saved before recording the final version. An explicitly uploaded replacement takes precedence over the saved draft attachment, and the superseded draft file is cleaned up.
- Added global framing restrictions, nosniff and referrer headers, production HTTPS enforcement, production HSTS, production debug suppression, and secure production session-cookie defaults. Preserved the stricter no-referrer headers on one-time mobile handoffs.
- Disabled debug output in the current local environment while preserving its local Microsoft callback URL. Added `.env.production.example` and updated the deployment runbook. Added Apache protection against browsing application files through the XAMPP project directory.
- Updated obsolete password-login, guest-page, history-location, and sorting-link tests to the current flows while retaining privacy, ownership, snapshot, filtering, pagination, and ordering coverage. Restored a scoped activity-log link in submission details and rejected ambiguous activity filters that supply a post ID without its module.
- Split the lazy-loaded editor engine from the rich-editor bundle. The production build no longer reports oversized JavaScript chunks.

## Verification

- **170 PHP tests passed; 2,276 assertions; zero failures or errors.** Includes regression coverage for failed profile-picture storage/database saves, private image access, legacy image migration, draft image promotion, production headers, HTTPS redirects, and cookie/debug defaults. Generated evidence: `audit/fixes-junit.xml` and `audit/fixes-tests.log`.
- **54 authenticated browser page checks passed** at 1440 and 390 pixels, covering student, officer, President, adviser, and superadmin roles. No JavaScript exceptions, broken images, or horizontal overflow were observed. The rich editor mounted successfully. Student attempts to open adviser management and pending media were denied. Evidence: `audit/fixes-browser.json` and `.tmp/security-browser/screenshots/`.
- Browser fixtures used an isolated SQLite database, cache, sessions, and media directory. They did not create or change real accounts.
- Landing-page browser checks passed at 1440, 768, 390, and 320 pixels, including keyboard controls, reduced motion, and the no-JavaScript fallback.
- Four real PDF preview checks passed for Documents and Liquidation at desktop and mobile sizes: canvas rendering, page status, authorized byte ranges, modal closing, and dialog overflow. Evidence: `audit/fixes-pdf-browser.json` and `.tmp/security-browser/pdf-screenshots/`.
- `npm run build` completed without the former large-editor warning.
- Live `composer audit --format=json` and `npm audit --json` both returned zero advisories.
- `php artisan migrate:status` confirmed no pending migrations on the configured PostgreSQL database.
- Git whitespace checks passed. Existing concurrent stylesheet, layout, and favicon changes were preserved.

## Deployment limits

A real Microsoft sign-in requires the account owner to complete the provider's login; the automated sign-in tests mock Microsoft. No production domain, TLS endpoint, or hosting administration session was supplied, so public hosting/proxy behavior and a backup restore were not tested or provisioned. Use the updated deployment runbook and production template when deploying. The local PHP 8.2.12 binary is old; production hosting must use a maintained PHP release with current security patches.

The separate Flutter application was not modified in this website task. Mobile website/API contracts remain covered by the PHP tests. The app-download button intentionally remains "coming soon" until an actual release URL is configured.
