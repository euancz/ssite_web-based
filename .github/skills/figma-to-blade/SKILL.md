---
name: figma-to-blade
description: Implement one approved Figma frame as a Laravel Blade visual change
disable-model-invocation: true
---
# Figma to Blade

Use this workflow for one selected Figma frame only.

1. Read `docs/FIGMA_CALL_LOG.md` and any matching note in `docs/figma/`. Ask the user how many Figma MCP read calls they have used this month, including calls outside this repository.
2. Apply the project ceiling of five calls per calendar month. If the user is at the ceiling, stop and offer the no-call workflow in `docs/FIGMA_NO_MCP_FALLBACK.md`. The reset is assumed to be a calendar month only until the user confirms it.
3. Confirm the user owns the Figma file or is authorized to share it. Do not request public access.
4. Ask the user to select exactly one frame and provide its selection link. Do not inspect a whole page.
5. Show the exact single design-context call you intend to make, including the selected frame, and wait for written approval. Do not make exploratory calls.
6. After approval, make exactly one design-context read for that frame, using the actual tool name exposed by the connected Figma server. Do not call image, screenshot, metadata, variable, or other additional Figma-read tools. If the server's tools differ from this prompt, stop and report the difference.
7. Immediately save all useful returned information to `docs/figma/<frame-name>.md`: structure, text, tokens/variables used, spacing, responsive hints, component hints, and an asset list. Never refetch a saved frame unless the user explicitly requests it.
8. List assets the user must export manually, including name, intended size, and format. Prefer PNG, SVG, or WebP exports into the project's approved asset location. Do not spend a Figma call on image retrieval.
9. Before editing, inspect the relevant existing Blade layout, CSS tokens, shared components, icon component, and security rules. Adapt the frame to the existing system by default.
10. Implement visual changes in Blade and CSS one section at a time. Do not change routes, controllers, models, policies, middleware, validation, queries, upload/download behavior, notifications, search behavior, or database behavior.
11. Preserve role-aware rendering, approved/active visibility, ownership checks, CSRF/method fields, escaped database output, private view-only PDFs, existing layouts, breadcrumbs, pill navigation, and menus.
12. Validate responsive and accessible behavior at 360px and 1440px. Compare against the user's exported image and report gaps honestly. If the context is truncated, use smaller user-approved sections only when the remaining call budget allows; otherwise use the fallback.
13. Update `docs/FIGMA_CALL_LOG.md` with the date, actual tool, frame, running count, and notes. Mention that the count is only the project workflow count.

No package installation is part of this workflow. Never write tokens, keys, passwords, or bearer credentials to a file. Use short why-comments only, with `SECURITY:` and `TODO:` prefixes where needed and Blade `{{-- --}}` comments in Blade.
