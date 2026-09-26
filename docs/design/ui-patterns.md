# UI patterns

Sets the page families, the components each family uses and a proposal per page. New pages and modules start from these patterns.

## Principles

- Consistent structure and behaviour matter more than an identical look. Pages that do the same job are built the same way.
- Colours carry meaning: green is fresh or OK, orange is a warning, red is stale or destructive. The same energy source keeps the same colour in every app.
- Colours and shared values come from one set of variables in `Theme/css/bootstrap5-theme.css`, with a light and a dark version. Page and app CSS do not set fixed colours. Exceptions: a component that sets both its background and its text, such as a pastel badge, and colours on the coloured top bar or the log window.
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

**Page header.** `h3` title on the left, page actions and the help link on the right, centred on the title (`Theme/css/bootstrap5-theme.css`).

```html
<div class="page-header">
    <h3>Feeds</h3>
    <a href="feed/api">Feed API Help</a>
</div>
```

**List layout.** For pages that list things: feeds, inputs, devices. Rows grouped by node or tag, collapsible, with selection and a sticky toolbar.

- Toolbar: `div.controls` with `btn btn-default` icon buttons and a filter field on the right. Each page makes it sticky with its own `*-controls` class and a sentinel element.
- List (`Theme/css/group-list.css`). Column widths are set per page on the `group-list` grid, with `data-col` on each cell.

| Class | Role |
|---|---|
| `group-list` | Grid container |
| `group-list-group` | One node or tag |
| `group-list-header` | Group row, `collapsed` when closed |
| `group-list-chevron` | Arrow in the header select cell |
| `group-list-name` | Group name |
| `group-list-rows` > `group-list-rows-inner` | Collapsible wrapper, `is-expanded` when open |
| `group-list-row` | Item row, `selected` when ticked |
| `group-list-cell` | Cell |
| `group-list-value` | Value cell text |
| `group-list-indicator` | Status bar in the updated cell |

- Status: `--status-color` on a header or row sets the stripe on its right edge and the indicator colour. Time since update in green or red text.

**Panel layout.** For pages of settings, forms and tools: graph, backup.

- Panel (`Theme/css/panel.css`):

| Class | Role |
|---|---|
| `panel` | Container |
| `panel-header` | Header row, pointer and hover |
| `panel-header-static` | Header that only labels the panel, no pointer or hover |
| `panel-accent` | Accent bar before the name, `panel-accent-danger` for destructive actions |
| `panel-name` | Title |
| `panel-badge` | Count or state beside the name |
| `panel-body` | Content with padding |
| `panel-controls` | Strip of fields and buttons |
| `panel-row` | Key and value row: `row-key`, `row-value`, `row-note`, then `row-action` icons and buttons. `is-editing` while an inline edit is open. |
| `row-action` | Icon action, shown on row hover and always on touch screens |
| `table` in a panel | Uppercase grey column heads, row hover. `col-primary` and `col-secondary` on cells. |

- Key and value rows: `panel-row`. Inline edit replaces the value with the field and Save and Cancel buttons. My Account is the example. Backup's `bk-row` can move onto it.
- Icons on converted pages: SVG icons (`svg-icon-*`), which follow the text colour.
- Several panels stack with the panel margin. A page may place panels in a Bootstrap grid (`row g-3` > `col-lg-6`).

**Controls.**

- Fields: `form-control` or `form-select` with a width class (`input-165`, `input-220` ...), `form-label` above, `input-group` for a label or unit beside the field. See the Forms section of `bootstrap5-migration.md`.
- Dates: `DateTimePicker`.
- Buttons: `btn-default` for ordinary actions, `btn-primary` for the main action of a panel or modal, `btn-danger` for delete and other destructive actions. One primary button per panel or modal.
- Status labels: `badge bg-success`, `bg-warning`, `bg-danger`, `bg-secondary`.
- On and off setting in a list row: `form-check form-switch`, as the Sync upload switch.
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

The API pages are dark with `data-bs-theme="dark"` on `.api-page` and `.api-explorer`, using the shared dark set. Their CSS is in `Lib/api_explorer.css`. The page background stays a fixed `#222` until the colour mode is set on `<html>`.

## Light and dark

The API pages and MyElectricFlow already share a dark look: `#2e2e2e` surfaces, `#333` to `#444` borders, `#fff`, `#ccc` and `#999` text, `#44b3e2` accent. They become one dark style.

- One set of variables in `bootstrap5-theme.css`, with a light version on `:root` and a dark version on `[data-bs-theme="dark"]`, the Bootstrap 5.3 colour mode.
- The dark accent is the brand blue `#44b3e2`. The light accent stays `#2a8fc7`, which reads better on white.
- A page or app turns dark with `data-bs-theme="dark"` on its root element. Components, Bootstrap and emoncms alike, follow the variables, so they work in both.
- For now each app keeps its own choice, and the API pages are dark. A site wide light or dark theme later sets the attribute on `<html>`.
- The app `dark.css` and `light.css` are replaced by the shared dark and light sets.

## Page proposals

| Page | Today | Proposal | Change |
|---|---|---|---|
| Inputs, Feeds, Devices | List layout | Keep. Page header component. Colours to variables. | Small |
| Graph | Panel layout | Keep. Move the 441 line `<style>` block to a CSS file. Colours to variables. | Small |
| Backup | Panel layout | Keep. Move the `<style>` block to a CSS file. | Small |
| My Account | Striped Bootstrap table | Done: panels Account, API keys, Profile, Appearance, Mobile app, Delete account. Inline edit with Save and Cancel. | Done |
| Post Process | Bootstrap table, well for Create new | Done: panel table for processes (no groups, so not the group list), panel form for new and edit. Run and Edit `btn-default`, Delete `btn-danger`. | Done |
| Sync | Older list and table | Done: Upload and Download tabs (`nav-tabs`). Two panels side by side: Remote server (a summary once linked, Change opens the form), then Upload service or Download by tab. Upload tab lists local feeds with an upload switch. Download tab lists remote feeds with Download buttons. Group list by tag with a sticky toolbar and filter as on Feeds. Status stripe green (up to date), orange (behind remote), blue (ahead). Sync Inputs and Sync Dashboards removed. | Done |
| Schedule | Bootstrap list and editor | Done: panel table for schedules (no groups, so not the group list) with Test, Edit and Delete. Editor in a panel below with Save and Cancel, rule blocks for the expression builder. New schedule opens the editor. | Done |
| Email Reports | Bootstrap form | Done: one tab per report (`nav-tabs`), Settings panel with `form-switch` for on and off options, Email preview panel with the subject row. The email keeps its light look in dark mode (`data-bs-theme="light"` on the email). | Done |
| Network | Blue page with white boxed rows | Panel layout in the shell: one panel per connection, WiFi scan in a panel. The blue look stays in the setup wizard only. | Medium |
| Admin pages | Mixed | Done: page header with actions (`page-actions`) on every page. Info: one panel per section with `panel-row` rows, status dot per service, Pi Control with the danger accent. Update: update actions as rows with blue `btn-primary` buttons (an exception to one primary per panel), firmware form panel, update log panel. Components: panel table, bulk branch switch in the header (Stable green, Master orange, Custom red, as before), two line rows (name and repo link, then remote tag and path), remote tag HTTPS purple or SSH amber as the feed engine badges. Log and Serial: log window in a panel, serial device settings in panels. Users: page header, CSS in a file. Shared CSS in `Modules/admin/static/admin_styles.css`. | Done |
| API pages | Dark reference | Done: CSS in a file, colours from the shared dark set. | Done |
| MyElectricFlow, Psychrograph | Dark app, own CSS | Source of the app kit. | Medium |
| MyHeatpump | Light app | Move to the kit's light set. | Medium |
| CO2 Monitor, UK Grid | Older `dark.css` | Rebuild with the app kit. | Medium each |
| Other apps | Older `dark.css` or `light.css` | Move to the kit one by one. The 14 without test instances need instances first. | Later |

## Roadmap

Each step is one or more commits per repo. Steps that should not change the look are checked with a before and after pixel diff. Steps that change the look are checked in the browser against a list of pages.

### 1. Dark variables

Done.

- Dark set on `[data-bs-theme="dark"]` in `bootstrap5-theme.css`: Bootstrap body, text, border and surface variables, primary `#44b3e2`, the emoncms tokens. Values from the API pages and MyElectricFlow.
- Component colours still written as literals in the theme (default button greys, input group text shadow, `code`, modal footer highlight, table stripe) move to variables so they follow the mode.
- API pages: `data-bs-theme="dark"` on their wrapper, local token overrides removed, `<style>` blocks moved to a CSS file.
- Check: API pages unchanged in a pixel diff. Setup components on a test page in both modes.

### 2. Setup kit

Done.

- `page-header` in the theme. Used by inputs, feeds, devices, schedules, dashboards, post process, sync, email reports, EmonHub config, my apps, available apps and my accounts. The `h2` titles moved to `h3`. Admin pages and network get it with their conversion.
- Group list and panel: CSS headers tidied, classes documented above. Unused panel rules removed (grid rows, row actions, collapse helpers, column variants).
- Colours to variables in `emoncms-base.css`, `menu.css`, `panel.css`, `group-list.css` and on inputs, feeds, devices, graph and backup. Unused `.node`, `.device-key` and `.gravitar` rules removed.
- Graph, backup and devices `<style>` blocks moved to `Modules/graph/view.css`, `Modules/backup/backup_view.css` and `Modules/device/Views/device_view.css`.
- Checked checkbox and radio in `--bs-primary`, with `accent-color` for native fields.
- Check: colour moves pixel identical in light. Look changes (header, `h3`, checkbox colour) checked on screenshots.
- Gaps seen in dark: glyphicon sprites stay black (step 5), graph "select a feed" box, feed engine badges (pastel, readable).

### 3. Page conversions

One page per commit. Each starts with a short proposal (layout sketch or mock up) for review, then the build, then a browser check.

1. My Account. Done.
2. Post Process. Done.
3. Sync. Done.
4. Schedule. Done.
5. Email Reports. Done.
6. Network. The setup wizard shares its view, so the wizard keeps the blue look and the Network page takes the panel layout.
7. Admin pages: info, log, components, update, serial, users. Done.

Colours move to variables as each page is converted.

### 4. App kit

- Shared app stylesheet in `Modules/app/Views/css/` from the MyElectricFlow components, on the shared light and dark variables. Energy colours as theme variables.
- MyElectricFlow and Psychrograph onto the kit, checked with a pixel diff.
- CO2 Monitor and UK Grid rebuilt with the kit, each from a proposal.
- MyHeatpump onto the light set.
- The other apps that load `dark.css` or `light.css` move one by one. The 14 without test instances need instances first. Then `dark.css` and `light.css` are removed.

### 5. Glyphicons to SVG icons

Low priority, last.

- `svg-icons.css` already has 82 icons drawn with CSS masks, in the text colour. The glyphicons have 244 uses, 82 names, across core and the module repos, in markup, JS strings and Vue class bindings.
- Map each glyph name to an SVG icon, adding missing ones. A converter script in `scripts/bootstrap5/` does the renames, as `cvt_classes.py` did.
- Remove `bootstrap2-icons.css` and the sprites in `Theme/img/`.
- Converted pages already use SVG icons.
- Check: icons change shape, so a browser check of the main pages. `icon-white` cases need a text colour instead.

### Release

The `bootstrap5` branch spans core and eleven module repos and is not pushed. It removes Bootstrap 2, `bootstrap2-legacy.css` and the old date picker, so third party modules that use Bootstrap 2 classes need changes. Release after the page conversions (step 3), with notes for module authors from `bootstrap5-migration.md`, and test on an emonSD image. Merge order: modules first, core last, in one release.

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
