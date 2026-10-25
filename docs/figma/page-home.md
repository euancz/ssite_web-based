# Public Home Reference

**Source:** User-provided `Home.png` export  
**Figma read:** Not used for implementation; the MCP read for node `228:1113` was blocked by file permissions.  
**Surface:** Normal/public home view, not the dashboard  
**Implementation date:** 2026-10-21

## Visual direction captured

- Bright cyan and pale blue SSITE palette with a white content surface.
- Public header followed by a playful illustrated welcome hero.
- Centered serif section headings with short dark-blue rules.
- Large featured news card followed by two smaller news cards.
- Cyan leadership strip with four-up officer cards.
- Sponsor/network cards on a pale neutral surface.
- Existing SSITE logo, Wolf image, and footer partner logos remain the local asset sources.

## Implementation notes

- Published home content continues to come from `publiclyVisible()` queries.
- Current officer snapshots are read through `OfficerTerm::current()->inDisplayOrder()`.
- The hero illustration is a CSS composition using the existing SSITE logo rather than a Figma screenshot or temporary asset.
- Sponsor cards are presentational placeholders until real sponsor content/assets are provided.
- Exact typography, spacing, hidden responsive states, and image crops are approximate because this implementation uses the attached export rather than live Figma design context.
