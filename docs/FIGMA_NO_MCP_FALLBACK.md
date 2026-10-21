# No-MCP Fallback

Use this workflow when the project ceiling is reached, the user does not approve a Figma read, the server is unavailable, the response would be truncated, or the user prefers not to send live design data to the AI service.

## User preparation

1. In Figma, export the selected frame as a PNG at 1x and 2x.
2. Attach both exported images to Copilot Chat.
3. Fill in [the tokens template](./figma/tokens-template.md) from Figma's styles and variables panels.
4. List any assets that need manual export, including intended dimensions and format. Export logos, icons, and images as PNG, SVG, or WebP into the approved project asset location.
5. State the frame name, target route/view, intended responsive widths, and any states that matter.

## Agent workflow

1. Read the attached images and the completed token template. Do not call any Figma tool.
2. Inspect the existing Blade layout, CSS tokens, shared components, icon component, and security rules.
3. Map the supplied values to existing CSS variables. Do not hard-code Figma values or add new tokens without asking first.
4. Implement Blade and CSS visual changes only, one section at a time.
5. Preserve routes, controllers, policies, middleware, validation, models, queries, notifications, search behavior, upload/download behavior, role-aware rendering, approved/active visibility, ownership checks, CSRF/method fields, escaped output, and private view-only PDFs.
6. Check the result at 360px and 1440px and report differences honestly.

Pixel values inferred from an image are approximate. The exported image is evidence of appearance, not a reliable source for exact computed styles, hidden states, semantics, or responsive rules. Prefer the values in the user's token template and the project's existing design system.

This workflow does not consume a Figma MCP read call and does not need a Figma file to be public. The user should still share only designs they own or are authorized to share.
