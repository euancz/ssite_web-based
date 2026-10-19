# SSITE Icon System

**Source:** `resources/views/components/icon.blade.php`  
**Library:** Lucide SVG, copied from `lucide-static@1.59.0`; see `THIRD_PARTY_NOTICES.md`.

## Concept map

| Concept | Lucide icon | Surfaces |
|---|---|---|
| Dashboard | `layout-dashboard` | Workspace navigation and dashboard |
| Article, document, PDF | `file-text` | Public/workspace content and attachments; visible “PDF” label remains alongside the icon |
| Archive | `archive` | Published/archived library and archived status badges |
| Pending / time | `clock` | Review and pending counts; pending status badges |
| Activity / date | `calendar-days` | Activity navigation and officer-year controls |
| Achievement | `award` | Achievement navigation and search suggestions |
| Liquidation / report | `chart-column-increasing` | Liquidation navigation and search suggestions |
| People / officer | `users` | Roster, user management, and officer search suggestions |
| Approved / success | `circle-check` | Review and approved status badges; always paired with text |
| Rejected / error | `circle-x` | Rejected status badges and error feedback; always paired with text |
| Search | `search` | Public search, search results, and management search |
| Notifications | `bell` | Public and workspace notification controls |
| Menu | `menu` | Public mobile navigation and workspace sidebar |
| Close | `x` | Public mobile navigation close state |
| Forward navigation | `chevron-right` | Workspace action links and error recovery |
| Disclosure | `chevron-down` | About leadership history and dropdown affordances |
| External destination | `external-link` | Preview, site, and social destination links |
| Add | `plus` | Officer roster |
| Profile | `user-round` | Account menu and profile links |
| Settings | `settings` | Existing account menu item |
| Sign out | `log-out` | Account menu |
| Address | `map-pin` | Public footer contact |
| Email | `mail` | Public footer contact |
| Overflow actions | `ellipsis` | Public and workspace post row menus |
| Like | `heart` | Existing feature-flagged Like action |
| Download | `download` | Existing authorized file action |
| Delete | `trash-2` | Existing management actions |
| Right arrow | `arrow-right` | Error-page return link |
| Up arrow | `arrow-up` | Public back-to-top control |
| Information | `info` | Informational feedback |
| Warning | `circle-alert` | Warning feedback |
| Search categories | `file-text`, `calendar-days`, `award`, `chart-column-increasing`, `users` | Public search suggestions |
| Notification states | `clock`, `circle-check`, `circle-x`, `info` | Notification dropdown and history status; paired with notification copy |

The Facebook destination retains its visible link text and uses the generic `external-link` icon. Lucide does not provide a Facebook trademark mark; no second icon set is added. SSITE and college logos and wolf imagery are brand assets, not UI icons.

Search suggestions and notification entries select only static Blade `<template>` fragments through fixed local maps. Unknown categories use a generic static icon; payloads never supply icon markup or component names.

## Component API

```blade
<x-icon name="search" size="md" />
```

The component accepts a static `name` and one size token:

| Token | Size |
|---|---:|
| `sm` | 16px |
| `md` (default) | 20px |
| `lg` | 24px |
| `empty` | 48px |
| `empty-lg` | 64px |

Stroke width is 2 and color is always `currentColor`. The SVG root is decorative (`aria-hidden="true"`, `focusable="false"`). The surrounding control supplies its accessible label and visible text. Icon-only interactive controls also need a visible tooltip on hover/focus and a 44×44px minimum target.

`<x-workspace.icon>` is a compatibility wrapper for existing workspace calls. New calls use `<x-icon>`. Its legacy names map only to the official map (`grid` → `layout-dashboard`, `file` → `file-text`, `calendar` → `calendar-days`, `chart` → `chart-column-increasing`, `check` → `circle-check`, `external` → `external-link`, `chevron` → `chevron-right`, `trash` → `trash-2`).

## Adding an icon

1. Confirm the concept in the official Lucide icon catalogue and the corresponding shipped `node_modules/lucide-static/icons/<name>.svg` file. Do not guess a name.
2. Copy only its static child shape markup into the `$icons` allow-list in `resources/views/components/icon.blade.php`; keep root viewbox, stroke, line caps/joins, sizing, and color centralized.
3. Add the concept, exact name, and use locations to this table. Include required license notices if the copied icon is covered by additional license terms.
4. Use only a literal template name or a fixed config value. Never source icon names from a request, database, or notification payload. Dynamic UI may select a pre-rendered, static Blade `<template>` through a local allow-list and clone its SVG; it must not inject SVG strings from remote/user data.
5. Keep icon meaning redundant with visible text. Unknown names render nothing; only local mode logs the missing static name.

## Licensing

Lucide SVG shapes are ISC-licensed. Some Lucide icons are derived from Feather and include additional MIT terms; the project notice records both notices. `lucide-static` is a temporary extraction-only development package and must not remain in the final dependency manifest/lockfile. Motion is MIT-licensed and is recorded in the same third-party notice.
