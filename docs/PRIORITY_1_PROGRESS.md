# Priority 1: public reading, workspace management, and safe editing

Approved by the owner after the October 26, 2026 inspection and plan. No production or local application records are changed by this work. No application migrations are run.

## Batch 1 — Test and schema baseline

- Replaced stale homepage, About, dashboard, and Adviser-review test expectations with current behavior. The archived-content authorization assertion was retained and the policy fixed.
- Added `Tests\Concerns\BuildsContentSchema`. It refuses to create fixtures unless the database is SQLite `:memory:`. Core content tables are reproduced explicitly because their original migrations are not in the repository. The test-only JSON_UNQUOTE compatibility function supports the existing MySQL notification query.
- Existing document gallery and PDF selection tests are retained.
- See `DATABASE_BASELINE_REVIEW.md` before considering any migration execution.

## Batch 2 — Public/workspace separation

- `ContentListing` applies visibility and ownership before filtering, counts, and pagination. Public lists accept only Published, including when staff request private tabs. Workspace lists expose My submissions, Published, Pending review, Rejected, and Archived; the President also has All posts.
- Added five authenticated `/workspace/posts/{module}/{id}` previews. Existing public routes remain, but public details now return 404 for pending, rejected, and archived content for every role.
- Content policies retain owner/President preview permission; workspace controllers additionally require the current module capability. PDF owner/President access remains unchanged and signed URLs still require authentication, profile completion, and file authorization.
- Public pages no longer render edit/archive/delete/review controls or switch shells based on the viewer's role. Create/edit routes retain their existing URLs and use the workspace shell.
- Dashboard, review, form, and notification destinations use workspace previews where appropriate. Notification JSON fields remain unchanged; clicking an authorized review notice resolves to the workspace. Share actions use the public URL only for approved active content.
- Liquidation search, document category/semester filters, and other module-specific sorting remain in place.

## Batch 3 — Shared interface foundations

- Preserved the SSITE logo, blue palette, navigation pill, and offset shadows. Public display headings use Pixelify Sans through the existing Google Fonts integration, with Poppins/sans-serif fallback. Restored the requested grid-paper background. Workspace body and controls retain readable body typography.
- Added `ui-foundation.css`, using existing semantic tokens for focus, forms, errors, tabs, tables, mobile layouts, and reduced motion.
- Added a public skip link and explicit main-content focus target. Workspace navigation retains its existing mobile focus trap, Escape handling, and collapse persistence.
- Five content forms share an error summary and saving feedback. Individual validation errors are associated with controls in server-rendered HTML, including without JavaScript. Back-to-submissions links preserve a clear exit.
- Shared image-preview JavaScript releases object URLs on replacement and page exit. PDF canvas restrictions and file delivery code remain intact.

## Batch 4 — Validation and persistence

- Five module-specific Form Requests preserve the existing fields, limits, and messages while authorizing the create/update operation. UploadedFile objects and forged ownership/workflow fields are excluded from model assignment.
- `PostFiles` commits record writes before removing replaced attachments; failed database writes remove only the newly stored upload. Cleanup is restricted to generated module paths. File cleanup failure is logged rather than turning a committed save into a failed response.
- Concurrent changes detected after model binding reject a stale save. Deletion locks the current record and deletes engagement rows and the record in one transaction before file cleanup.
- `ContentReview` locks and rechecks the record, rejects duplicate or changed decisions, and sends existing notifications only after commit. President-only authorization and required rejection reasons remain in place.
- No drafts, rich text, scheduling, taxonomy, analytics counters, or history tables were added; these remain Priority 2/3 work.

## How a save works

Blade posts the existing CSRF-protected form to the existing route. Route middleware and the Form Request check the user's module and ownership permission. The Form Request validates editable values; controllers set ownership and workflow fields themselves. `PostFiles` uses an Eloquent transaction to save the row and coordinates file cleanup. The controller then redirects to the authorized workspace preview. Review decisions use the same policies and a locked transaction before notifying the author/reviewers.

## Verification

Results are recorded after the final checks. Automated coverage includes public/private content boundaries for all five modules, workspace ownership, direct unauthorized mutation requests, review/restore behavior, private signed PDF delivery and expiry, upload validation, failed-save cleanup, stale updates, duplicate review notifications, escaped content, Microsoft callback/domain/profile behavior, and notification ownership/JSON shape.

Live Microsoft OAuth and rendered desktop/tablet/mobile browser interaction checks require an interactive browser session; automated callback tests use a mocked Microsoft identity. SQLite tests do not replace MySQL integration or production deployment review.
