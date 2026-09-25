# UI patterns

Sets the page families, the components each family uses and a proposal per page. New pages and modules start from these patterns.

## Principles

- Consistent structure and behaviour matter more than an identical look. Pages that do the same job are built the same way.
- Colours carry meaning: green is fresh or OK, orange is a warning, red is stale or destructive. The same energy source keeps the same colour in every app.
- Colours and shared values come from one set of variables in `Theme/css/bootstrap5-theme.css`, with a light and a dark version. Page and app CSS do not set fixed colours.
- Page CSS goes in a `.css` file loaded with `load_css`, not in a `<style>` block.
- Reuse a component before writing a new one. A new component goes in this guide.

## Families

| Family | Purpose | Look | Best current examples |
|---|---|---|---|
| Setup pages | Managing things and settings | Light, grey and white, in the emoncms shell | Inputs, Feeds (lists), Graph, Backup (panels) |
| Apps | Dashboards for the household | Dark or light, chosen per app | MyElectricFlow (dark), MyHeatpump (light) |
| Reference | API documentation | Dark | API help pages |

### Setup pages

A setup page is one of two layouts, both under a page header.

**Page header.** `h3` title on the left, page actions and the help link on the right. Today each page builds its own (`#feed-header` with a floated link, plain `h2` or `h3` elsewhere). One theme component replaces them.

```html
<div class="page-header">
    <h3>Feeds</h3>
    <a href="feed/api">Feed API Help</a>
</div>
```

**List layout.** For pages that list things: feeds, inputs, devices. Rows grouped by node or tag, collapsible, with selection and a sticky toolbar.

- Toolbar: `div.controls` with `btn btn-default` icon buttons and a filter field on the right (`Theme/css/emoncms-base.css`).
- List: `group-list` > `group-list-group` > `group-list-header` and `group-list-rows` > `group-list-row` > `group-list-cell` (`Theme/css/group-list.css`). Column widths are set per page on the grid.
- Status: `group-list-indicator` for the coloured edge, time since update in green or red text.

**Panel layout.** For pages of settings, forms and tools: graph, backup.

- `panel` > `panel-header` (`panel-accent`, `panel-name`, actions on the right) > `panel-body` or `panel-controls` (`Theme/css/panel.css`).
- Key and value rows: `panel-grid` with `grid-row`, `row-value` and `row-action`.
- Several panels stack with the panel margin. A page may place panels in a Bootstrap grid (`row g-3` > `col-lg-6`).

**Controls.**

- Fields: `form-control` or `form-select` with a width class (`input-165`, `input-220` ...), `form-label` above, `input-group` for a label or unit beside the field. See the Forms section of `bootstrap5-migration.md`.
- Dates: `DateTimePicker`.
- Buttons: `btn-default` for ordinary actions, `btn-primary` for the main action of a panel or modal, `btn-danger` for delete and other destructive actions. One primary button per panel or modal.
- Status labels: `badge bg-success`, `bg-warning`, `bg-danger`, `bg-secondary`.
- Modals: Bootstrap 5 modal as in `bootstrap5-migration.md`.

### Apps

Apps share a kit taken from MyElectricFlow, the best finished app. Today MyElectricFlow has its own 580 line stylesheet, Psychrograph borrows it, and the other apps use the older `Modules/app/Views/css/dark.css` or `light.css`.

The MyElectricFlow components move into one shared app stylesheet in `Modules/app/Views/css/`, built on the shared light and dark variables. `dark.css` and `light.css` retire once the apps have moved.

| Component | MyElectricFlow classes |
|---|---|
| App frame and tabs | `app-top-bar`, tab links with the accent underline |
| Live values | `power-title`, `power-value`, `power-unit` in a `stats-grid` |
| Time bar | `visnavblock` with `visnav` buttons, manual date range with `ctrl-group` |
| Label and field pair | `ctrl-group`, `ctrl-label`, `ctrl-note`, `ctrl-checkbox` |
| Buttons | `app-btn`, `cost-btn` |
| Chart panel | chart placeholder in a dark panel, flot tooltip classes |
| Flow blocks | `statsbox`, `statsbox-title`, `statsbox-value`, `statsbox-units`, arrows |
| Tables | `statstable`, `tariff-table` |

App colours:

- Surfaces, text, borders and accent come from the shared light and dark variables.
- Energy colours shared by all apps, as variables in the theme: use, solar, grid import, grid export, battery charge, battery discharge, heat. MyElectricFlow's values are the starting point (use blue, solar yellow, export green, battery orange, grid red). Each has a light and a dark value where needed.
- An app sets `data-bs-theme="dark"` or leaves it light.

### Reference

The API pages keep their dark look and are the model for the dark variables. Their CSS sits in `<style>` blocks in `Lib/api_explorer_view.php` and `Lib/api_auth_view.php`. It moves to a CSS file, and its local token overrides are replaced by `data-bs-theme="dark"` with the shared dark set.

## Light and dark

The API pages and MyElectricFlow already share a dark look: `#2e2e2e` surfaces, `#333` to `#444` borders, `#fff`, `#ccc` and `#999` text, `#44b3e2` accent. They become one dark style.

- One set of variables in `bootstrap5-theme.css`, with a light version on `:root` and a dark version on `[data-bs-theme="dark"]`, the Bootstrap 5.3 colour mode.
- The dark accent is the brand blue `#44b3e2`. The light accent stays `#2a8fc7`, which reads better on white.
- A page or app turns dark with `data-bs-theme="dark"` on its root element. Components, Bootstrap and emoncms alike, follow the variables, so they work in both.
- For now each app keeps its own choice, and the API pages are dark. A site wide light or dark theme later sets the attribute on `<html>`.
- The API explorer's local token overrides and the app `dark.css` and `light.css` are replaced by the shared dark and light sets.

## Page proposals

| Page | Today | Proposal | Change |
|---|---|---|---|
| Inputs, Feeds, Devices | List layout | Keep. Page header component. Colours to variables. | Small |
| Graph | Panel layout | Keep. Move the 441 line `<style>` block to a CSS file. Colours to variables. | Small |
| Backup | Panel layout | Keep. Move the `<style>` block to a CSS file. | Small |
| My Account | Striped Bootstrap table | Panel layout: Account (user ID, username, email, API keys, password, delete), Profile (gravatar, name, location, timezone, language, starting page), Appearance (theme colour, sidebar colour, archived features), Mobile app. Edits stay inline with the pencil. | Medium |
| Post Process | Bootstrap table, well for Create new | List layout for processes (name, parameters, mode, status badge, actions), panel for Create new. Run, Edit, Delete as `btn-default` and `btn-danger`. | Medium |
| Sync | Older list and table | Panel for the remote connection and settings. List layout for remote feeds, grouped by tag, matching Feeds. | Medium |
| Schedule | Bootstrap list and editor | List layout for schedules, editor in a panel. | Medium |
| Email Reports | Bootstrap form | Panel layout, one panel per report. | Small |
| Network | Blue page with white boxed rows | Panel layout in the shell: one panel per connection, WiFi scan in a panel. The blue look stays in the setup wizard only. | Medium |
| Admin pages | Mixed | Panel layout. Review page by page. | Medium |
| API pages | Dark reference | Keep the look. CSS to a file, colours from the shared dark set. | Small |
| MyElectricFlow, Psychrograph | Dark app, own CSS | Source of the app kit. | Medium |
| MyHeatpump | Light app | Move to the kit's light set. | Medium |
| CO2 Monitor, UK Grid | Older `dark.css` | Rebuild with the app kit. | Medium each |
| Other apps | Older `dark.css` or `light.css` | Move to the kit one by one. The 14 without test instances need instances first. | Later |

## Roadmap

Each step is one or more commits per repo. Steps that should not change the look are checked with a before and after pixel diff. Steps that change the look are checked in the browser against a list of pages.

### 1. Dark variables

- Dark set on `[data-bs-theme="dark"]` in `bootstrap5-theme.css`: Bootstrap body, text, border and surface variables, primary `#44b3e2`, the emoncms tokens. Values from the API pages and MyElectricFlow.
- Component colours still written as literals in the theme (default button greys, input group text shadow, `code`, modal footer highlight, table stripe) move to variables so they follow the mode.
- API pages: `data-bs-theme="dark"` on their wrapper, local token overrides removed, `<style>` blocks moved to a CSS file.
- Check: API pages unchanged in a pixel diff. Setup components on a test page in both modes.

### 2. Setup kit

- `page-header` component in the theme, used by every setup page. Pages with an `h2` title move to `h3`.
- Group list and panel: tidy their CSS headers and document their classes in this guide.
- Colours to variables in the shell (`emoncms-base.css`, `menu.css` with the colour schemes, `panel.css`, `group-list.css`) and the pages that keep their layout: inputs, feeds, devices, graph, backup.
- Graph and backup `<style>` blocks move to CSS files.
- Check: pixel diff unchanged in light. The same pages viewed in dark for gaps.

### 3. Glyphicons to SVG icons

- `svg-icons.css` already has 82 icons drawn with CSS masks, in the text colour. The glyphicons have 244 uses, 82 names, across core and the module repos, in markup, JS strings and Vue class bindings.
- Map each glyph name to an SVG icon, adding missing ones. A converter script in `scripts/bootstrap5/` does the renames, as `cvt_classes.py` did.
- Remove `bootstrap2-icons.css` and the sprites in `Theme/img/`.
- Before the page conversions, so converted pages use SVG icons from the start.
- Check: icons change shape, so a browser check of the main pages. `icon-white` cases need a text colour instead.

### 4. Page conversions

One page per commit. Each starts with a short proposal (layout sketch or mock up) for review, then the build, then a browser check.

1. My Account
2. Post Process
3. Sync
4. Schedule
5. Email Reports
6. Network. The setup wizard shares its view, so the wizard keeps the blue look and the Network page takes the panel layout.
7. Admin pages: info, log, components, update, serial, users.

Colours move to variables as each page is converted.

### 5. App kit

- Shared app stylesheet in `Modules/app/Views/css/` from the MyElectricFlow components, on the shared light and dark variables. Energy colours as theme variables.
- MyElectricFlow and Psychrograph onto the kit, checked with a pixel diff.
- CO2 Monitor and UK Grid rebuilt with the kit, each from a proposal.
- MyHeatpump onto the light set.
- The other apps that load `dark.css` or `light.css` move one by one. The 14 without test instances need instances first. Then `dark.css` and `light.css` are removed.

### Release

The `bootstrap5` branch spans core and eleven module repos and is not pushed. It removes Bootstrap 2, `bootstrap2-legacy.css` and the old date picker, so third party modules that use Bootstrap 2 classes need changes. Proposal: release the migration before the page conversions (after step 3), with notes for module authors from `bootstrap5-migration.md`, and test on an emonSD image. Merge order: modules first, core last, in one release.

### Later

- Site wide light or dark theme as a user setting, next to theme colour. Needs the remaining fixed colours in page CSS moved to variables.
- Colour schemes also set the primary colour.
- Inline `style` attributes and `!important`: tidy when a page is converted, not as a sweep.
- HTML docs section with a style guide.

## Decisions

- Page title: `h3`.
- Apps keep their own light or dark choice for now. A site wide light or dark theme may come later.
- The API pages and MyElectricFlow converge on one dark style, built as the dark version of the shared variables.
- No component page in emoncms for now. This guide is the reference. An HTML docs section with a style guide may come later.
