# Private drafts

Implemented October 27, 2026 following explicit approval to apply the draft-storage migration and continue implementation.

## Completed behavior

- Save draft on all five create forms stores incomplete text and editable metadata, without publishing or sending review notifications. Work in progress can exceed the publication word limit, up to the bounded draft field size.
- My drafts is available in the workspace sidebar and through a private draft count on posting-role dashboards. The list paginates and filters by currently permitted content area.
- Resume restores saved fields into the existing create form. Existing escaped Blade output and local text-only previews keep markup inert.
- Drafts belong only to their author. A President cannot read or change another author's unsubmitted draft. Losing a module capability hides its drafts without deleting them; regaining it restores access.
- Save, submit, and delete compare a version number under a database row lock. Stale tabs receive an error instructing the author to copy unsaved text and reopen the latest draft.
- Final submission uses the existing module Form Request, upload validation, authorization, approval settings, and notifications. Draft deletion and content insertion share one transaction. Validation or persistence failure retains the draft; transaction failure removes a newly stored upload. Repeating a consumed draft submission cannot create another post.
- Deleting a draft uses an explicit confirmation disclosure and a CSRF-protected DELETE request with the current version.

**Attachment limitation:** Drafts retain text and metadata only. Selected images/PDFs are not stored by Save draft and must be selected again on final submission. The controls and save confirmation explain this. Existing private PDF viewing restrictions remain intact. This feature creates new submissions; it does not stage revisions of already published posts.

## Architecture

`ContentDraft` maps the new UUID-keyed table and casts the JSON payload. `SaveContentDraftRequest` authorizes the selected module and accepts only editable fields, a draft ID, and its version; workflow fields, ownership, and storage paths are ignored. `ContentDrafts` scopes every read/write to the current owner and current module policies.

`ContentDraftController` provides the four new `workspace.drafts.*` endpoints: list, resume, save, and delete. They require authentication and profile completion; writes use the existing content-write throttle. Existing endpoints and JSON contracts are unchanged.

The five create-form partials use saved draft values only as defaults beneath Laravel's validation-error input. A shared draft-controls component supplies the save action, explicit file limitation, and version fields. Save uses ordinary HTML `formaction` and `formnovalidate`, so incomplete drafts work without JavaScript. Normal submission still validates all required content and PDFs.

On creation, each content controller delegates its save to `ContentDrafts::submit`. `PostFiles::save` accepts an optional transaction callback, allowing draft consumption to commit with the post while retaining its failed-upload cleanup. Existing edit/archive/review behavior is unchanged.

## Database and verification

- Applied only `2026_10_27_000001_create_content_drafts_table` to the configured `ssitecms` database after approval. Existing table definitions and content were not changed. The new table and its normal migration-ledger entry are the application database changes made by this batch.
- Artisan reported the targeted migration completed successfully. The subsequent October 27 read-only MySQL recheck passed: migration ledger, columns, index, and owner foreign key are present. Application draft records were not seeded for testing.
- **60 tests, 1,027 assertions passed**, including seven new draft integration tests covering all module forms, ownership versus President access, role changes, stale saves/submissions/deletions, cross-module submission, forged fields, XSS, bounded drafts, file requirements, duplicate submission, notifications, and cleanup on failed submission.
- Production Vite build, Blade compilation/cache clear, PHP formatting, and whitespace checks passed. The design detector reported no findings on the new views/styles.
- Headless Chrome checked draft list, resume form, and create form at 1440px and 320px with no page overflow. Save draft reached the expected endpoint with JavaScript enabled and disabled, despite incomplete required publication fields. Screenshots used isolated SQLite fixtures; browser POST destinations were stubbed, while real Laravel mutations were exercised by feature tests.
- Chrome emitted two view-transition opt-in warnings when navigating to the minimal stub POST responses; the submitted destination checks passed. This is not a claim of a full live Microsoft-authenticated browser session.

## Remaining work

Rich text and server sanitization, featured posts, durable draft attachments, scheduling, immutable history, and additional analytics remain separate work. Do not run unrelated pending migrations or drop `content_drafts` as a routine rollback. Saved drafts must be preserved unless their deletion is explicitly authorized.

Document academic years are now prepared separately; see [migration review](DOCUMENT_ACADEMIC_YEAR_REVIEW.md).

