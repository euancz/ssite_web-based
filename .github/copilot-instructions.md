# SSITE Copilot Instructions

## Figma to Blade

These rules apply to every Figma-to-code request in this Laravel application:

- Treat the Figma MCP server as a constrained, user-approved design-context source. Trust the server's actual tool list over this document because the server is evolving. If tool names, capabilities, permissions, or limits differ, stop and report the difference.
- The project ceiling is five Figma read calls per calendar month, leaving one spare relative to the currently documented Starter/View/Collab allowance. Never exceed it without explicit written approval in the same chat.
- A Figma read call is any tool that reads data from Figma. Do not make exploratory calls. Work on one frame selected and approved by the user per request.
- At the start of each Figma session, read `docs/FIGMA_CALL_LOG.md` when it exists and ask the user for the real monthly count, including calls made outside this workflow.
- Show the exact single planned call and wait for the user's written approval before making it. If the ceiling is reached, stop and use `docs/FIGMA_NO_MCP_FALLBACK.md`.
- Save useful structure, text, variables/tokens, spacing, assets, and component hints immediately to `docs/figma/<frame-name>.md`. Never refetch a frame that already has a saved note unless the user explicitly asks for a refresh.
- If a response is truncated, split the frame into smaller user-selected sections and spend another call only if the budget permits; otherwise use the no-call fallback.
- Use Blade views and CSS only. Never introduce React, Vue, or Tailwind classes unless the project is verified to already use Tailwind for that surface; report that finding first.
- Use the existing CSS variables and shared components. Never hard-code Figma colors, fonts, spacing, radii, or shadows. Map each value to the nearest project token and report when no suitable token exists.
- Use `<x-icon>` and the existing Lucide icon allow-list. Never import another icon set.
- Keep `layouts/app.blade.php`, `layouts/workspace.blade.php`, breadcrumbs, the pill navigation, and role-aware menus.
- Make visual changes only. Do not change routes, route names, controllers, policies, middleware, validation, `$fillable`, models, migrations, queries, upload/download logic, notification logic, or search logic.
- Preserve security rules: controls a role may not use stay unrendered in HTML with `@can` or capability helpers; students and guests see only approved and active items; non-public items return 404; ownership compares `created_by_user_id` with `user_id`, never `->id`; forms use `@csrf` and `@method`; database output uses `{{ }}`, never `{!! !!}`; PDFs remain private and view-only; file paths are never exposed.
- Preserve the public and management layouts and existing security boundaries. Designs that conflict with the design system are adapted to existing tokens by default; ask before adding tokens.
- Design responsively from 360px first. Use 44px minimum tap targets, 4.5:1 contrast, visible focus, alt text, `aspect-ratio` plus image width and height, reduced-motion support, and content that remains readable without JavaScript.
- Work on one frame per request. Compare the result with the user's exported image at 360px and 1440px and report differences honestly. Never claim pixel-perfect or perfect copying.
- Connect only Figma files the user owns or is authorized to share. Figma data is sent to the AI service; do not ask the user to make a private file public.
- Use short why-comments only. Use `SECURITY:` and `TODO:` prefixes where comments are needed, and use Blade `{{-- --}}` comments in Blade files.

Do not install packages or edit `composer.json` or `package.json` for this workflow. Do not put tokens, keys, passwords, or bearer credentials in the repository. If a second Figma server is configured, stop and ask which one to remove rather than using both.
