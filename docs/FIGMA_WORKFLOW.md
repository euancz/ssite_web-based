# Figma Workflow

This workflow is designed for a free or otherwise call-limited Figma account. It uses one approved frame per request and saves the result so the same frame does not need to be read again.

## Before a call

1. Confirm that the Figma file is owned by the user or that the user has permission to share it with the AI service. Never make a private file public just to use this workflow.
2. Check the user's real monthly read-call count, including calls made outside this repository. The project ceiling is five calls per calendar month.
3. Select one frame, not the entire Figma page, and copy its selection link.
4. Show the exact intended design-context call and wait for written approval.
5. Check `docs/FIGMA_CALL_LOG.md` and `docs/figma/` for an existing note. Reuse a saved note unless the user explicitly requests a refresh.

## Prepare the Figma file

- Use one frame per page or major section.
- Name frames consistently, for example `Page / Home`, `Page / Articles`, and `Workspace / President dashboard`.
- Keep each frame reasonably small so the response is useful and not truncated.
- Use auto layout for reusable groups and responsive relationships.
- Use components for repeated controls and content patterns.
- Use Figma variables and styles for colors, type, spacing, radii, and shadows.
- Name layers, states, and variants clearly.
- Include meaningful text rather than placeholder strings when the copy matters.
- Keep states that matter to the implementation in separate, clearly named frames or variants.

## Spend the call well

The approved read should capture structure, text, layout relationships, variables/tokens used, spacing, responsive hints, component hints, and an asset list. Save that information immediately in `docs/figma/<frame-name>.md`.

Do not use another read to retrieve images, logos, or icons. Export those manually as PNG, SVG, or WebP into the approved project asset location. Record the intended name, dimensions, and format in the frame note.

If a response is truncated or too large, split the frame into smaller sections only after checking the remaining ceiling and obtaining approval for the additional call. Otherwise stop and use [the no-call fallback](./FIGMA_NO_MCP_FALLBACK.md).

## Budget math

The project ceiling is five read calls per month. A conservative plan is one call for each of five selected pages or major sections. Although current Figma documentation reports a larger Starter/View/Collab allowance, the project intentionally keeps one spare relative to the previously expected six-call budget and requires the user to confirm the account's actual limits. Do not write or assume unlimited usage.

The call log is not an account meter. It records only this project's workflow, so the user must provide the complete monthly count at the beginning of every session.

## After the call

1. Save one durable note under `docs/figma/` immediately.
2. List assets for manual export instead of spending a call on image retrieval.
3. Implement visual changes only, using existing Blade layouts, CSS tokens, shared components, and the existing Lucide icon component.
4. Compare the result with the user's exported image at 360px and 1440px and report differences honestly.
5. Record the actual tool, frame, date, running project count, and notes in [the call log](./FIGMA_CALL_LOG.md).

## When the limit is reached

Stop making Figma reads. Use [the no-call fallback](./FIGMA_NO_MCP_FALLBACK.md): the user exports the frame, attaches PNGs to Copilot Chat, and supplies the token details by hand. Confirm when the account's limit resets; a calendar month is only an assumption until verified.

If the connected server's tool names, permissions, or limits differ from this workflow, trust the actual tool list, stop, and tell the user what differs. If two Figma servers are configured, stop and ask which one should remain.
