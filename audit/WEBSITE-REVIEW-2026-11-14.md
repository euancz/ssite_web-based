# Website functionality and security review

**Follow-up:** application fixes and fresh verification are recorded in [WEBSITE-FIXES-2026-11-14.md](WEBSITE-FIXES-2026-11-14.md). The findings below describe the original review state.

Reviewed the local SSITECMS Laravel website on 14 November 2026. This is a source review, automated test run, local HTTP smoke test, dependency audit, and landing-page browser check. It is not a certification of the deployed website or a complete penetration test. Application source and configuration were not changed.

## Assessment

The website has substantial working functionality and useful security controls, but it should not receive an unconditional “everything works and is safe” sign-off. Dependency updates, deployment hardening, and test maintenance remain necessary. A reproducible profile-picture failure path also needs fixing.

## Findings, in priority order

### 1. Update dependencies with published security advisories before deployment

Live `composer audit --format=json` reported 10 advisories across seven installed packages:

| Package | Installed version | Advisories |
| --- | --- | --- |
| laravel/framework | 12.69.2 | Concurrency task output injection |
| phpseclib/phpseclib | 4.0.1 | CPU exhaustion while parsing encrypted keys/PFX/CMS |
| symfony/http-foundation | 7.4.19 | Four advisories covering proxy trust, request methods, script paths, and generated URL hosts |
| symfony/http-kernel | 7.4.19 | Forwarded headers affecting generated URLs |
| symfony/mime | 7.4.19 | Email header injection |
| symfony/routing | 7.4.18 | Host placeholders affecting path matching |
| symfony/yaml | development dependency; see composer.lock | Excessive parser runtime |

Live `npm audit --json` reported two affected packages: `follow-redirects` (high severity) and Vite 6.4.4 (moderate severity). The audit identifies patched versions 1.16.2 and 6.4.5 respectively. Both are in the development dependency tree in this project; this does not establish a browser-side exploit against the production bundle. The Vite issue matters particularly if the development server is reachable by untrusted clients.

These are installed-version matches, not demonstrations that every advisory is reachable through this application's routes. Review applicability and update the lockfiles, then repeat the build and tests. Do not apply a blind forced dependency upgrade.

References: [Symfony generated URL advisory](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693), [Vite advisory](https://github.com/advisories/GHSA-8g5g-mxqh-grgj), [follow-redirects advisory](https://github.com/advisories/GHSA-mrwp-f3q8-3g9p).

### 2. Current environment is configured for local development

The local environment has `APP_ENV=local`, `APP_DEBUG=true`, and an HTTP application URL. The actual local session cookie has HttpOnly and SameSite=Lax, but lacks Secure. This is a deployment blocker **if these settings are used on a public host**; their presence in a local checkout alone does not prove a production misconfiguration.

Use production mode, disable debug, configure HTTPS and secure cookies, and serve only the `public` directory. Keep application secrets, repository metadata, logs, and audit artifacts outside public access. The local PHP server correctly returned 404 for `/.env`, `/composer.json`, and `/.git/config`. The Apache/production virtual-host configuration was not validated.

Evidence: `.env` non-secret flags, `config/session.php:172`, and local response headers. No secret values are included in this report.

### 3. Profile-picture replacement can destroy the previous picture on failure

`app/Http/Controllers/Profile/ProfileController.php:94` stores the replacement, deletes the old picture at line 99, and only then saves the new database path at line 103. It does not check whether `storeAs` returned false. The local public filesystem configuration permits false returns (`throw=false`).

If storage returns false, the code can delete the old image and save an invalid replacement path while reporting success. If the database save fails after a successful upload, the old image has already been deleted and the new upload can become orphaned. The current Supabase configuration throws on storage failure, but the database-failure ordering problem still applies.

Validate the stored path, save the database change successfully before deleting the old image, and clean up the replacement when persistence fails. Add focused failure-path coverage. This finding is based on code inspection; no real user's picture was modified.

### 4. Pages lack an application-level restriction on framing

Local responses have neither `X-Frame-Options` nor a CSP `frame-ancestors` directive, and no global middleware implementing them was found. This leaves the application without an explicit clickjacking defense at this layer. SameSite cookies provide some mitigation for cross-site framing; they are not equivalent to a framing restriction, including for same-site untrusted origins.

Set an appropriate framing policy globally, such as CSP `frame-ancestors 'self'` or `'none'`, after accounting for the app's intended embedding. A reverse proxy may already add this on a deployed host; that remains unverified. A complete CSP should be designed around the existing inline scripts rather than added without compatibility checks.

### 5. The test suite is not green and contains obsolete expectations

The rerun with filesystem access completed 158 tests and 2,045 assertions: **134 passed, 23 failed, and one errored**. Results are in `audit/review-junit-unrestricted.xml`.

The first sandboxed run had additional upload/PDF filesystem errors. Those disappeared in the rerun, so they should not be reported as confirmed application defects.

The remaining results group as follows:

- Eight tests expect guest access to content/about pages that now require authentication.
- Six ExampleTest cases expect the former landing page, password login endpoints, or login popover. Microsoft is now the only sign-in flow.
- Five review-history browsing tests call a legacy route that now redirects to content activity; assertions do not follow or target the replacement route.
- Two review-history detail tests expect content no longer rendered in the same location.
- One document filter test expects a complete sort hyperlink, while the UI now uses form controls; its database filtering and pagination assertions passed before that assertion failed.
- One Microsoft login test expects the officer dashboard, while the controller deliberately redirects completed accounts to `/home`.
- One featured article test reads article view data from `/`, which now renders the separate guest landing page.

Update these tests to the intended current behavior while retaining their authorization, filtering, history, and ordering assertions. Do not simply remove the failed tests. Until that work is done, the suite cannot provide a clean regression signal for all those flows.

### 6. Unpublished image attachments use public storage

Article/activity/achievement uploads use the public disk before approval, and their models produce public image URLs. Publishing gates protect the post records, but anyone who obtains a public attachment URL can retrieve the image independently of post authorization. Archiving or resetting approval does not revoke that URL. Random filenames reduce discoverability but are not an access check.

If pending/rejected/archived images must be confidential, keep them private and serve them through authorization or promote them to public storage only on approval. This is a storage-design observation; the live Supabase bucket ACL was not tested.

## Checks that passed

- `npm run build`: successful; Vite warns about a chunk larger than 500 kB. This is a performance consideration, not a build failure.
- Existing headless Chrome landing checks at 1440, 768, 390, and 320 pixels: no horizontal overflow, broken images, or invalid fragment links; app-section navigation and FAQ keyboard operation passed; reduced-motion and no-JavaScript visibility checks passed. Evidence: `../landing-preview/verification.json` and refreshed screenshots.
- `/` and `/up`: HTTP 200.
- Guest `/home`, `/articles`, `/documents/1/view`, and `/adviser/users`: redirected away from protected content.
- Unauthenticated `/api/mobile/v1/home`: HTTP 401.
- Sensitive application/repository paths on the local PHP server: HTTP 404.
- Rerun upload persistence, draft conflict handling, private PDF range responses, and mobile private-file tests passed.
- Source controls include default web CSRF middleware, server-side content policies, role/capability checks, restricted profile mass assignment, Microsoft OAuth state handling and school email checks, upload validation, bounded write/upload rates, escaped Markdown HTML, restricted link schemes, private PDF paths, and signed web PDF requests.

The landing page's app download is explicitly disabled as “coming soon.” It is intentionally unavailable until a release URL is configured.

## Still unverified

- Real Microsoft sign-in and account provisioning against the configured tenant; the authentication tests mock the provider.
- A full authenticated browser walkthrough for student, officer, President, adviser, and superadmin roles. Feature tests exercise many server flows but do not replace browser interaction checks.
- Deployed TLS, HTTP redirects, proxy headers, web-server document root, public reachability of development tools, and security headers.
- Live PostgreSQL/Supabase schema parity, row-level security, bucket access rules, uploaded production assets, backups, and restoration. Tests used an isolated SQLite in-memory database and local/fake disks.
- The separate Flutter client in `ssiteapp`, which is outside this website review.

Recommended next steps: patch affected dependencies, fix profile-picture persistence, configure production protections, repair the outdated tests, and then complete authenticated browser and deployment checks.
