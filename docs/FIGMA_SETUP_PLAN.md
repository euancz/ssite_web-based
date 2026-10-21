# Figma-to-Blade Setup Plan

**Date:** 2026-10-21  
**Phase:** Phase 0 audit and Phase 1 plan only  
**Scope:** Configuration, Copilot rules, and workflow documentation. No application page, behavior, route, database, dependency, or design implementation changes are included.

## 1. Audit

### Repository state

- Branch: `main`, tracking `origin/main`.
- Worktree: clean at the time of the audit; no staged, unstaged, or untracked changes were reported.
- Existing Figma setup progress file: not present.
- Existing `.vscode/` directory: not present.
- Existing `.github/` directory: not present.
- Existing `.github/copilot-instructions.md`: not present.
- Existing `.github/prompts/`: not present.
- Existing `.github/skills/` or `.github/instructions/`: not present.
- Existing MCP configuration found in the repository: none.
- No duplicate Figma MCP server was found in repository files.

### Existing design and shared UI locations

- Design tokens and shared CSS variables: `public/css/app.css`, with the Tailwind entry and matching font token in `resources/css/app.css`.
- Public layout: `resources/views/layouts/app.blade.php`.
- Management layout: `resources/views/layouts/workspace.blade.php`, with shared workspace markup in `resources/views/layouts/partials/workspace.blade.php`.
- Shared Blade UI components: `resources/views/components/ui/`.
- Shared icon component: `resources/views/components/icon.blade.php`; the project documents the Lucide-based allow-list in `docs/ICONS.md`.
- Existing design guidance and audit records: `docs/DESIGN_SYSTEM.md`, `docs/DESIGN_REVIEW.md`, `docs/DESIGN_UPGRADE.md`, `docs/DESIGN_UPGRADE_PROGRESS.md`, `docs/ICONS.md`, and related progress/plan documents.
- Security and application behavior constraints are documented in `docs/SYSTEM_OVERVIEW.md`, including the `user_id` primary key and public-content visibility rules.

### Documentation inventory

The repository already contains `docs/SYSTEM_OVERVIEW.md`, `docs/WHERE_IS_WHAT.md`, `docs/DESIGN_SYSTEM.md`, `docs/DESIGN_REVIEW.md`, `docs/ICONS.md`, and other feature/design plan and progress records. The requested Figma setup files do not exist yet.

### Ignore and local-settings audit

`.gitignore` excludes `.env`, `.env.backup`, and `.env.production`. It also excludes the entire `/.vscode` directory, as well as common editor directories such as `/.idea`, `/.fleet`, `/.nova`, and `/.zed`.

This means local editor settings are protected from accidental tracking, but `.vscode/mcp.json` would also be ignored. The build phase must decide whether to explicitly force-add only the non-secret shared MCP configuration, or add a narrowly scoped ignore exception. No change is proposed in this phase. Any local settings file or credential-bearing file must remain ignored; no credential file is part of this plan.

### Current Figma documentation findings

The current official Figma MCP documentation says:

- The remote endpoint is `https://mcp.figma.com/mcp`.
- Starter and View/Collab seats currently have **up to 20 read-tool calls per month**.
- Full/Dev seats on Professional have up to 200 tool calls per day and 10 per minute; Education plans have the same stated limits as Full/Dev seats on Professional.
- Figma says rate limits may change and that limits apply to tools that read data from Figma.
- The remote MCP server is recommended by Figma; the desktop server has different seat and plan requirements.

Sources checked on 2026-10-21:

- [Figma MCP server guide](https://help.figma.com/hc/en-us/articles/32132100833559-Guide-to-the-Figma-MCP-server)
- [Figma MCP server FAQs and rate limits](https://help.figma.com/hc/en-us/articles/39252411778583-Figma-MCP-server-FAQs)
- [Figma pricing and seats](https://www.figma.com/pricing/)

The requested workflow therefore uses a conservative **five-call project ceiling**, one call below the currently documented Starter/View/Collab monthly allowance. The agent must still ask for the user’s real account-specific count because this project cannot observe calls made elsewhere.

## 2. Proposed Phase 1 deliverable

Create only `docs/FIGMA_SETUP_PLAN.md` in this phase. It records this audit and the implementation plan below. No `.vscode`, `.github`, application, CSS, JavaScript, route, database, package, or other documentation file is changed now.

## 3. Files to create or merge after approval

### Configuration

1. **`.vscode/mcp.json`** — create the single official remote Figma server entry, with no token or other credential:

   ```json
   {
     "inputs": [],
     "servers": {
       "figma": {
         "url": "https://mcp.figma.com/mcp",
         "type": "http"
       }
     }
   }
   ```

   Before adding it, verify that no other Figma server is configured. Because `/.vscode` is currently ignored, the build batch must explicitly resolve whether this shared file should be force-added or whether a narrowly scoped ignore exception is acceptable. No broad ignore change or secret-bearing local settings file is planned.

### Copilot rules and reusable prompt

2. **`.github/copilot-instructions.md`** — create it if absent, otherwise merge without replacing existing content. Add a `Figma to Blade` section that preserves the current Laravel security, role, layout, token, icon, accessibility, responsive, comment-style, PDF, and view-only rules. It will require one selected frame per request, an exported-image comparison at 360px and 1440px, honest gap reporting, and the five-call policy.

3. **`.github/prompts/figma-to-blade.prompt.md`** — create a reusable `/figma-to-blade` workflow. It will read the call log, ask for the real monthly count, stop at the ceiling, show exactly one intended call and wait for written approval, make one approved `get_design_context` call only, save the result immediately, list assets for manual export instead of calling `get_image`, implement one section at a time, compare against the user’s exported image, and update the log. Frontmatter will use agent-mode metadata only if the installed VS Code version supports it; otherwise it will omit that metadata and state the compatibility limitation.

### Workflow records

4. **`docs/FIGMA_CALL_LOG.md`** — create a dated table with `Date`, `Tool`, `Frame`, `Calls used this month (running)`, and `Notes`. The header will state that the log covers only this project workflow and cannot know about calls made elsewhere.

5. **`docs/FIGMA_WORKFLOW.md`** — document Figma preparation, frame selection, auto layout, components, variables, naming, reasonable frame size, manual asset exports, five-call operating math, the Figma reset uncertainty, and the no-call fallback.

6. **`docs/FIGMA_NO_MCP_FALLBACK.md`** — document the zero-call process: export PNG at 1x and 2x, attach it to Copilot Chat, fill in the hand-written token notes, map values to existing CSS variables, and treat image-measured pixels as approximate.

7. **`docs/figma/tokens-template.md`** — provide fields for colors, fonts, sizes, line heights, spacing, radii, shadows, breakpoints, assets, and notes about the source Figma styles/variables.

8. **`docs/figma/README.md`** — explain that one note belongs to each selected frame and that saved notes must be reused instead of refetching the frame.

9. **`docs/FIGMA_MANUAL_STEPS.md`** — list the steps only the user can perform: account/education-plan checks, design-file preparation, variables/styles, VS Code Agent mode, browser OAuth sign-in, confirming the server and tools appear without running a Figma read, restarting VS Code if needed, permissions, and privacy.

10. **`docs/FIGMA_SETUP_PROGRESS.md`** — create during the first approved build batch and update after every later batch with status and a dated entry. It does not get created in this Phase 1-only batch unless the user approves moving to Phase 2.

### Existing documentation updates, only after approval

11. **`docs/SYSTEM_OVERVIEW.md`** — update in place without reformatting. Add the new Figma setup records, the five-call policy, and “If I want to change X, open Y” guidance. The exact sections changed will be reported.

12. **`docs/WHERE_IS_WHAT.md`** — update in place without reformatting. Add where to change the monthly ceiling, add a Figma rule, switch to the fallback, and reuse a saved frame note. The exact sections changed will be reported.

No page, controller, model, policy, middleware, validation, migration, database, route, package manifest, build output, or public asset will be changed.

## 4. Call-budget policy to apply everywhere

1. Project default ceiling: **5 Figma read calls per calendar month**, leaving one spare relative to the currently documented 20-call Starter/View/Collab allowance. The agent never exceeds five without explicit written approval in that chat.
2. A call means any tool that reads data from Figma. Exploratory reads are forbidden; each call must target a frame the user selected and approved.
3. Before every call, show the exact intended call and wait for the user’s approval.
4. Save useful returned structure, text, variables/tokens, spacing, assets, and component hints immediately in `docs/figma/<frame-name>.md`.
5. Never refetch a frame that already has a saved note unless the user explicitly requests a refresh.
6. If the project ceiling is reached, use the no-call fallback and tell the user that the reset timing is account-dependent and must be confirmed; the working assumption is a calendar month.
7. Trust the actual MCP tool list over stale documentation. If names, capabilities, or limits differ, stop and report the difference.
8. Never claim exact or perfect visual copying. Adapt designs to the project’s existing tokens by default and ask before adding new tokens.
9. Connect only Figma files the user owns or is authorized to share. Figma data is sent to the AI service, so privacy and permission must be confirmed before a read.

## 5. Figma file conventions

- Use one reasonably sized frame per page or major section.
- Name frames with the default convention `Page / Home`, `Page / Articles`, and `Workspace / President dashboard`.
- Select the frame, not the whole page, when requesting context.
- Use auto layout, components, and Figma variables for colors, typography, spacing, radii, and shadows.
- Name layers and states clearly.
- Export logos, icons, and images manually as PNG, SVG, or WebP into the project’s approved asset location rather than spending a Figma read call.
- Store design notes under `docs/figma/`, using a stable frame name.
- If a frame response is truncated, split the design into smaller sections and spend another call only after checking the remaining budget; otherwise use the fallback.

## 6. Fallback workflow

The user exports the selected frame as PNG at 1x and 2x, attaches it to Copilot Chat, and fills out `docs/figma/tokens-template.md` from Figma’s styles and variables panels. The agent maps values to existing SSITE tokens, uses the shared Blade layouts/components and `<x-icon>`, implements visual changes only, and reports any token mismatch or approximate image measurement. No Figma MCP read is needed.

## 7. Manual-only steps

The user must confirm the Figma account, plan, seat, education eligibility, file ownership/permission, and real monthly read-call count; create or prepare the Figma file; configure variables/styles and frame names; open VS Code with Copilot Agent mode; start the server from `.vscode/mcp.json`; sign in through the browser; confirm the server and tool names appear without running a design-read tool; restart VS Code if the tools do not appear; and decide how the ignored `.vscode/mcp.json` should be tracked. The user must not put credentials in the repository.

## 8. Questions requiring confirmation before Phase 2

Defaults are shown in bold.

1. Which Figma plan and seat do you have? **Free Starter with a View or Collab seat.** Does your school email qualify for Education? **Unknown; the user will check.**
2. Which server should be used? **Official remote server.** Third-party local Framelink server: **no**. If selected later, its token handling, pinned version, and separate limits require a separate approval and must use a VS Code password input, never a committed file.
3. What monthly project ceiling should the workflow enforce? **Five read calls**, unless you explicitly approve a different ceiling.
4. Which frame naming convention should be used? **`Page / Home`, `Page / Articles`, `Workspace / President dashboard`.**
5. Where should saved design notes live? **`docs/figma/`.**
6. Should the reusable saved prompt be added? **Yes.**
7. How should the shared `.vscode/mcp.json` be tracked while `/.vscode` remains ignored? **Default: explicitly force-add only this reviewed, credential-free file; do not unignore the directory broadly.**

## 9. Approval gate

Phase 2 must not begin until the user approves this plan and answers or accepts the defaults above. After approval, work proceeds in small batches and stops after each batch for a separate “go.” The first approved batch will create or merge only the agreed configuration/instruction files and will write `docs/FIGMA_SETUP_PROGRESS.md`; later batches will add the workflow records and update the two existing overview documents.

