# Figma Setup: Manual Steps

These steps require the user because they involve account access, browser sign-in, file permissions, or editor UI. Do not place credentials, tokens, keys, or passwords in the repository.

## Account and file

- [ ] Confirm the Figma account, plan, and seat.
- [ ] Check whether the school email qualifies for the Education plan.
- [ ] Create or open a Figma file that I own or am authorized to share.
- [ ] Keep the file private unless its normal permissions already allow the intended work; never make it public just for MCP.
- [ ] Prepare variables and styles for colors, typography, spacing, radii, and shadows.
- [ ] Use the frame convention `Page / Home`, `Page / Articles`, or `Workspace / President dashboard`.
- [ ] Use one reasonably sized frame per page or major section, with auto layout and clearly named layers.

## VS Code and MCP

- [ ] Open the repository in VS Code with GitHub Copilot Agent mode available.
- [ ] Start the `figma` server configured in `.vscode/mcp.json`.
- [ ] Complete OAuth sign-in in the browser when prompted by the official remote server.
- [ ] Confirm that VS Code lists exactly one Figma server and its tools.
- [ ] Confirm the tools appear without running a Figma design-read tool.
- [ ] Restart VS Code if the server or tools do not appear.
- [ ] If another Figma server is present, stop and remove one configuration rather than using both.
- [ ] Confirm how the credential-free `.vscode/mcp.json` will be tracked while the existing `/.vscode` ignore rule remains in place.

## Before each design request

- [ ] Check `docs/FIGMA_CALL_LOG.md`.
- [ ] Tell the agent the complete number of Figma read calls used this month, including calls made outside this repository.
- [ ] Select one frame and copy its selection link.
- [ ] Confirm the file is authorized for sharing with the AI service.
- [ ] Approve the exact single call shown by the agent.
- [ ] Export logos, icons, and images manually as PNG, SVG, or WebP instead of spending a call on image retrieval.
- [ ] After implementation, review the exported comparison at 360px and 1440px.

## Privacy and permissions

Figma design data is sent to the AI service through the MCP workflow. Connect only files I own or am authorized to share, and do not include confidential material unless the applicable organization and file policies allow it. The project workflow has a five-read-call ceiling; the log cannot see calls made in another client or repository.
