# UI patterns

Sets the page families, the components each family uses and a proposal per page. New pages and modules start from these patterns.

## Goal

Reduce the amount of CSS in the application significantly. This includes:

- Tree shaking Bootstrap, keeping the Bootstrap 5 class names and their intent.
- Modern CSS: variables, colour modes, grid and flex in place of hand rolled layout.
- Reuse. One component in the theme replaces the copies in page CSS.
- Clear design patterns, set out in this guide, for future development.

Progress is measured against the goal metrics below.

## Principles

- Consistent structure and behaviour matter more than an identical look. Pages that do the same job are built the same way.
- Colours carry meaning: green is fresh or OK, orange is a warning, red is stale or destructive. The same energy source keeps the same colour in every app.
- Colours and shared values come from one set of variables in `Theme/css/bootstrap5-theme.css`, with a light and a dark version. Page and app CSS do not set fixed colours. Exceptions: a component that sets both its background and its text, such as a pastel badge, and colours on the coloured top bar or the log window.
- Page CSS goes in a `.css` file loaded with `load_css`, not in a `<style>` block.
- Reuse a component before writing a new one. A new component goes in this guide.

## Goal metrics

Measured on core and the module repos at the end of each roadmap step, with the scripts below, and compared with the figures of 27 September 2026. Sizes are bytes, raw and gzipped, as the browser downloads the gzipped file and a minified file has no useful line count. Hand written CSS is every `.css` file except `bootstrap.min.css`, `bootstrap2-icons.css`, `svg-icons.css` and `montserrat.css`, with `node_modules`, `vendor` and `tools` skipped and the module symlinks followed, plus the `<style>` blocks in views.

| | Raw | Gzipped | Rules | Selectors | Declarations |
|---|---|---|---|---|---|
| Hand written `.css`, 45 files | 199876 | 41182 | 1705 | 1859 | 4673 |
| `<style>` blocks, 20 views | 18654 | 4326 | 178 | 200 | 447 |
| `bootstrap.min.css` | 232111 | 30768 | 2550 | 2961 | 5543 |

After step 4, app kit (27 September 2026): hand written `.css` 48 files, 185102 raw, 38793 gzipped, 1580 rules, 1705 selectors, 4304 declarations. `<style>` blocks 11 views, 10942 raw, 3009 gzipped, 97 rules. Together 10% smaller raw. Hex literals 333, `!important` 62.

After the app CSS tidy (`utils.css` removed, `app.css` to the app list page only): hand written `.css` 47 files, 182282 raw, 38269 gzipped, 1550 rules, 1673 selectors, 4291 declarations. `<style>` blocks 10 views, 10055 raw, 88 rules. Hex literals 330, `!important` 49.

After apps on Bootstrap components: hand written `.css` 47 files, 177582 raw, 37573 gzipped, 1511 rules, 1626 selectors, 4153 declarations. `<style>` blocks unchanged. Hex literals 343 and `!important` 53, counted with `/usr/bin/grep`; the earlier figures went through the interactive `ugrep` wrapper and are not comparable. This step removed one `!important` and no hex literals.

After the reference pages on Bootstrap (API, Network, app config panel, 27 September 2026): hand written `.css` 176725 raw, 37608 gzipped, 1516 rules, 4121 declarations. Hex literals 339, `!important` 39.

After the reference page components (27 September 2026): hand written `.css` 172677 raw, 37188 gzipped, 1469 rules, 3948 declarations. Hex literals 337, `!important` 39. The API, Network and config sheets went from 24383 bytes before step 1 to 10922, plus 3259 in the theme.

Hex and `!important` from here on are counted with `colcount.py`, which reads the working tree and `HEAD` with one pattern. Before step 5 (`HEAD`, 27 September 2026): hex literals 448 in `.css` files, 321 outside the theme, 16 in `<style>` blocks, `!important` 39.

Step 5, tags (27 September 2026): hand written `.css` 168533 raw, 36540 gzipped, 1427 rules, 1542 selectors, 3833 declarations. Hex literals outside the theme 260, `!important` 39.

Step 5, sticky list toolbar: hand written `.css` 165697 raw, 36306 gzipped, 1401 rules, 1516 selectors, 3748 declarations. Hex and `!important` unchanged.

Step 5, panel form: hand written `.css` 164828 raw, 36253 gzipped, 1376 rules, 1491 selectors, 3714 declarations.

Step 5, page defaults and button resets: hand written `.css` 162910 raw, 36121 gzipped, 1327 rules, 1440 selectors, 3660 declarations.

Step 5, tokens: hand written `.css` 162161 raw, 35904 gzipped, 1327 rules, 1440 selectors, 3635 declarations. Hex literals outside the theme 260, `!important` 39.

Step 5, leftovers: hand written `.css` 162128 raw, 36032 gzipped, 1327 rules, 1440 selectors, 3636 declarations. `<style>` blocks 9 views, 9873 raw, 87 rules. Hex literals outside the theme 260, 13 in `<style>` blocks, `!important` 39.

End of step 5 against the step 4 figures (177582 raw, 37573 gzipped, 1511 rules, 4153 declarations): hand written `.css` 9% smaller raw, 4% gzipped, 184 rules and 517 declarations fewer. Against `HEAD` before step 5 (172677 raw, 37188 gzipped, 1469 rules): 10549 bytes, 1156 gzipped and 142 rules fewer. Hex literals outside the theme 321 to 260.

Bootstrap tree shake, dead rules and demandshaper (27 September 2026): `bootstrap.min.css` is built from the Bootstrap Sass with unused components left out, then purged against the source by `purge.mjs`, keeping the grid and the utility families whole. 97854 raw, 16092 gzipped, 1045 rules, 1261 selectors, 2485 declarations, against 232111, 30768 and 2550 rules for the stock file. A pixel and computed style diff of every page state against the stock file showed no difference. Hand written `.css` 46 files, 155339 raw, 34706 gzipped, 1265 rules, 1365 selectors, 3444 declarations, after 3.5 KB of dead rules found by `deadcss.mjs` and the demandshaper conversion. `<style>` blocks 8 views, 9812 raw, 86 rules. Hex literals outside the theme 228, `!important` 37.

- Hand written CSS gets smaller. A page copy moved into the theme counts, as the page loses more than the theme gains. Minifying does not, which is why rules, selectors and declarations are counted next to the bytes.
- Bootstrap size is set by the source scan in `purge.mjs`, not browser coverage. `bsmissing.mjs` lists classes the source uses that the build lacks.
- Fixed colours outside `bootstrap5-theme.css` go to zero, apart from the exceptions in the principles: 450 hex literals.
- `!important` gets rarer: 69 uses.
- Every theme variable has a use.

Figures come from `cssbytes.sh`, `csscount.py`, `colcount.py` and `deadcss.mjs`. These are kept outside the repository.

## Families

| Family | Purpose | Look | Best current examples |
|---|---|---|---|
| Setup pages | Managing things and settings | Light, grey and white, in the emoncms shell | Inputs, Feeds (lists), Graph, Backup (panels) |
| Apps | Dashboards for the household | Dark or light, chosen per app | MyElectricFlow (dark), MyHeatpump (light) |
| Reference | API documentation, network setup | Dark | API help pages, Network |

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

- Toolbar: `div.list-toolbar` with `btn btn-default` icon buttons, then the filter field or page actions pushed right with `ms-auto`. An empty `div.list-toolbar-sentinel` goes above it, and the page calls `list_toolbar(sentinel, '.list-toolbar')` from `Theme/js/emoncms.js` once both are rendered. The toolbar sticks under the top menu with a bar in the menu colour (`is-sticky`).
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
| `list-toolbar`, `list-toolbar-sentinel` | Sticky toolbar above the list |

- Status: `--status-color` on a header or row sets the stripe on its right edge. Time since update in green or red text.

**Panel layout.** For pages of settings, forms and tools: graph, backup, admin, post process, schedule, email reports, sync, My Account.

- Page: `panel-page` on the page root (theme). Grey page background, content 1150px wide, bottom padding, 1rem between panels. A page that needs another width sets `max-width` on `main.content-container:has(.its-page)`. `page-lead` for the line under the page header. `[v-cloak]` is in the theme.

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
| `panel-form` | Stacked form in the panel body: `panel-field` blocks with a `form-label` above the field, then `panel-buttons` |
| `panel-empty` | Message in place of an empty table |
| `row-action` | Icon action, shown on row hover and always on touch screens |
| `table` in a panel | Uppercase grey column heads, row hover. `col-primary` and `col-secondary` on cells, `panel-actions` on the buttons cell, `is-editing` on the row open in the editor. |
| `panel-table` | Wrapper for a table that scrolls sideways. The page sets the table `min-width` on narrow screens. |

- Key and value rows: `panel-row`. Inline edit replaces the value with the field and Save and Cancel buttons. My Account is the example. Backup's `bk-row` can move onto it.
- Icons on converted pages: SVG icons (`svg-icon-*`), which follow the text colour.
- Several panels stack with the panel margin. A page may place panels in a Bootstrap grid (`row g-3` > `col-lg-6`).

**Controls.**

- Fields: `form-control` or `form-select` with a width class (`input-165`, `input-220` ...), `form-label` above, `input-group` for a label or unit beside the field. See the Forms section of `bootstrap5-migration.md`.
- Dates: `DateTimePicker`.
- Buttons: `btn-default` for ordinary actions, `btn-primary` for the main action of a panel or modal, `btn-danger` for delete and other destructive actions. One primary button per panel or modal.
- Status labels: `badge bg-success`, `bg-warning`, `bg-danger`, `bg-secondary`.
- Pastel tags (method, access, state): `badge px-2 bg-success-subtle text-success-emphasis`, with `primary`, `secondary`, `info`, `warning`, `danger`, `purple` or `orange` in place of `success`. No page CSS needed: the theme sets the subtle and emphasis colours in both modes, and adds the purple and orange utilities Bootstrap lacks. Used by the feed engine badges, admin pages, backup, the API pages, Network and the app config panel. The user avatar takes the same classes for its colour.
- Monospace text: `font-monospace` (theme sets `--bs-font-monospace`).
- On and off setting in a list row: `form-check form-switch`, as the Sync upload switch.
- Modals: Bootstrap 5 modal as in `bootstrap5-migration.md`.

### Apps

Apps share one kit, `Modules/app/Views/css/app-kit.css`, on the shared light and dark variables. Dark apps take their look from MyElectricFlow (panels), light apps from MyHeatpump (blocks). Every app is on the kit. The older `dark.css`, `light.css` and `graph.css` are removed.

An app loads the kit with `load_css`, wraps its view (app block, config and loader) in `div.app-page` and sets `data-bs-theme="dark"` or `"light"` on it. Light apps also load `Lib/fonts/montserrat/montserrat.css`, which the kit applies to a light `.app-page`. App specific CSS goes in a file beside the app.

```html
<div class="app-page" data-bs-theme="dark">
    <section id="app-block" style="display:none">
        <div class="app-panel">
            <nav class="app-top-bar">
                <div id="tabs" class="nav nav-underline">button.nav-link tabs</div>
                <div class="nav">config-open and config-close nav-link buttons</div>
            </nav>
            <div class="stats-grid">...</div>
        </div>
        <div class="app-panel">time bar and chart</div>
    </section>
    appconf include, ajax-loader
</div>
```

Light app, as MyHeatpump:

```html
<div class="app-page" data-bs-theme="light">
    <section id="app-block" style="display:none">
        <div class="app-block">
            <div class="app-bar">
                <div class="app-bar-title">MY HEATPUMP</div>
                <button class="app-bar-btn config-open"><span class="svg-icon-wrench"></span></button>
            </div>
            <div class="app-block-body app-stats">...</div>
        </div>
        <div class="app-block">bar with time buttons, body with the chart, foot</div>
    </section>
    appconf include, ajax-loader
</div>
```

| Component | Classes |
|---|---|
| App frame | `app-page`, `app-panel` rounded blocks. The first panel has a top margin. |
| Top bar and tabs | `app-top-bar` (flex, space between) holding a `nav nav-underline` of `button.nav-link` tabs (icon and label, accent underline when `active`) and a `nav` of icon `nav-link` buttons (`config-open`, `config-close`) |
| Buttons | Text or toggle button: `nav-link` in a `nav`, underlined when `active`. Action: `btn btn-outline-primary` (accent tint, defined in the theme), `active` for the chosen option. |
| Live values | `stats-grid` (three columns, `stats-grid-2` for two) of `power-title`, `power-value`, `power-unit`. Colour with the energy classes. |
| Energy colours | `text-use`, `text-house`, `text-solar`, `text-wind`, `text-direct`, `text-import`, `text-export`, `text-battery`. Bootstrap `text-*` classes keep their Bootstrap meaning. |
| Time bar | `app-navbar` row (safe to show and hide from JS) with a `btn-group app-timebar` of plain `btn` buttons, then notes and a `nav ms-auto` of `nav-link` toggles such as Daily. Manual date range: `input-group` fields with `DateTimePicker.attach`, and a one button `app-timebar` for Done. |
| Fields | `input-group w-auto` > `input-group-text` + `form-select` or `form-control` (theme width classes), a trailing `input-group-text` for a unit. `form-check` for a checkbox, `small text-body-secondary` for a note. `.app-page .input-group` has no bottom margin, so fields line up with buttons in a flex row. |
| Flow blocks | `statstable` of `statsbox` cells: `statsbox-title`, `statsbox-value`, `statsbox-units`, `statsbox-prc`, arrows `statsbox-arrow-down`, `-right`, `-left` in `--statsbox-color`. `statsbox-energy` on a box filled with an energy colour, with the fill `statsbox-solar`, `-import`, `-export`, `-battery` or `-house`. |
| Tables | `table` (`table-sm` for dense ones), `col-primary` for the name cell, `app-swatch` colour square |
| Blocks (light apps) | `app-block` > `app-bar` (grey header bar: `app-bar-title`, `app-bar-btn` buttons with `active`, `app-bar-spacer` to push the following buttons right), `app-block-body` (white), `app-block-foot` (grey summary strip) |
| Block values | `app-stats` row of equal columns, each `app-stat-title`, `app-stat-value` with `app-stat-unit`, `app-stat-sub` for a small line below |
| Option rows | `app-option`: checkbox and bold label, with fields (`input-group`) below when ticked. Rows stack with shared borders. |
| Config panel | `Lib/appconf`, shared by every app, dark in all apps. Header with the app name and Launch app, readiness strip, App and About cards, feeds as two-column rows (status circle, key, node, AUTO, DERIVED or REQUIRED tag, click to edit in place), unused optional feeds behind a Show button, kWh flow feeds card, options as rows with switches, Manage rows. The first `.lead` paragraph of `#appconf-description` becomes the header line. Classes `cfg-*` in `appconf.css`. |
| Charts | Flot 5 legend panel and tick labels follow the mode inside `.app-page`. Tick labels are SVG text, so a `font` option needs `fill`. Unlabelled series need `label: ""` to stay out of the legend. Tooltip classes `tooltip-title`, `tooltip-value`, `tooltip-units` keep fixed colours, as the tooltip is added to `body`. |

App colours:

- Surfaces, text, borders and accent come from the shared light and dark variables, plus `--ec-app-panel-bg`, `--ec-app-box-bg` and `--ec-app-bar-bg` (block header bar).
- Time bar blue: `--ec-app-nav-rgb`, used as `rgba(var(--ec-app-nav-rgb), a)`.
- Energy colours shared by all apps: `--ec-energy-use`, `--ec-energy-use-light`, `--ec-energy-solar`, `--ec-energy-import`, `--ec-energy-export`, `--ec-energy-battery`, `--ec-energy-direct`, `--ec-energy-wind`, and `--ec-energy-text` for text on them. Values from MyElectricFlow. Heat is added with MyHeatpump. Chart series colours are still set in each app's JS.
- The page background and footer sit outside `.app-page`, so the kit sets them to fixed dark values when a dark `.app-page` is on the page.

### Reference

The API pages, the Network page and the app config panel share one set of components, section 5 of `Theme/css/bootstrap5-theme.css`, on Bootstrap cards and the shared dark set. Each page sets `data-bs-theme="dark"`. The page background stays a fixed `#222` until the colour mode is set on `<html>`.

| Component | Classes |
|---|---|
| Page | `ref-page`, width from `--ref-width` (1040px default; API 1150px, Network 800px) |
| Title | `ref-head` with `h2` and a lead `p` in the first child, actions after it |
| Card | Bootstrap `card`, or `card card-body` for content. Radius, spacing and colours are set on `.ref-page .card`. `open` gives the accent border. Two cards side by side: `row row-cols-1 row-cols-lg-2 g-3`. |
| Card title | `ref-card-title` with a `ref-icon` |
| Section heading | `ref-section` holding an `h4.ref-label`, then a count or buttons |
| Label | `ref-label`, the uppercase grey label, also for sidebar groups |
| Icon circle | `ref-icon`, `ref-icon-sm` on rows. A state colour with `bg-success-subtle text-success-emphasis` and the like. |
| Row | `ref-row` in a card: icon, text, values, actions. `is-link` for rows that open in place; the open card gets `open`. |
| Code | `ref-code` for URLs, request bodies and responses |
| Tags | `badge px-2 bg-*-subtle text-*-emphasis` |

Page CSS keeps what is particular to the page: the API sidebar, parameter grid and chevron (`Lib/api_explorer.css`), the WiFi signal bars and row parts (`network_view.css`), the feed grid and value cells of the config panel (`appconf.css`). The setup wizard turns blue with `net-blue`, which overrides the `--bs-*` variables the components read.

## Light and dark

The API pages and MyElectricFlow already share a dark look: `#2e2e2e` surfaces, `#333` to `#444` borders, `#fff`, `#ccc` and `#999` text, `#44b3e2` accent. They become one dark style.

- One set of variables in `bootstrap5-theme.css`, with a light version on `:root` and a dark version on `[data-bs-theme="dark"]`, the Bootstrap 5.3 colour mode.
- The dark accent is the brand blue `#44b3e2`. The light accent stays `#2a8fc7`, which reads better on white.
- A page or app turns dark with `data-bs-theme="dark"` on its root element. Components, Bootstrap and emoncms alike, follow the variables, so they work in both.
- For now each app keeps its own choice, and the API pages are dark. A site wide light or dark theme later sets the attribute on `<html>`.
- The app `dark.css` and `light.css` are replaced by the shared dark and light sets. Done.

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
| Network | Blue page with white boxed rows | Done: API page look, dark. One column as the original: connection rows (Ethernet, WiFi, Hotspot) with state tags, choice rows, then WiFi networks as rows with signal bars that open in place for the password. Close on the list, a failed state after 60 s without an IP address. The setup wizard shares the markup in blue (`net-blue`). | Done |
| Admin pages | Mixed | Done: page header with actions (`page-actions`) on every page. Info: one panel per section with compact `panel-row` rows, status dot and state tag per service, coloured service buttons as before, branch tag, component chips with LC tag, usage bars green, amber or red, Pi Control with the danger accent. Update: Full Update and Update Database as rows with blue `btn-primary` buttons (an exception to one primary per panel), firmware form panel, update log panel. Update Emoncms Only removed: use Full Update or the Components page. Components: panel table, Update all (each component on its current branch) and bulk branch switch in the header (Stable green, Master orange, Custom red, as before), rows grouped by install folder, one line per component, Source badge (HTTPS purple or SSH amber, as the feed engine badges) that links to the repo. Log and Serial: log window in a panel, serial device settings in panels. Users: page header, CSS in a file, initials avatar in a pastel colour per username, Admin, Verified and feed count tags as the feed engine badges. Sort by clicking a column head, search in the panel header. Shared CSS in `Modules/admin/static/admin_styles.css`. | Done |
| API pages | Dark reference | Done: CSS in a file, colours from the shared dark set. | Done |
| MyElectricFlow, Psychrograph | Dark app, own CSS | Done: source of the app kit, onto it without a visible change. Psychrograph legend fixed (dark panel, no "Plot N" entries). | Done |
| MyHeatpump, MyBoiler | Light app | Done: source of the light blocks. Grey header bars with buttons, white bodies, value rows, option rows. MyBoiler loads the MyHeatpump stylesheet in place of its copy. | Done |
| CO2 Monitor, UK Grid | Older `dark.css` | Done: rebuilt with the app kit. CO2 Monitor: top bar, time bar and chart, sensor panel with Average and Decay buttons, totals (volume, mean CO2, air change rate) beside the daily CO2 addition field, sensor table. UK Grid: Fuel mix and Forecast tabs, time bar, series toggles as a row above the chart, source notes panel. | Done |
| Other light apps | Older `light.css` | Done: Time of Use, Feed-in, Octopus, Cost Comparison on the light blocks. Blue bars now grey. | Done |
| Other dark apps | Older `dark.css` | Done: MyElectric, MyElectric2, Time of Use 2 (flexible), MyEnergy, MySolar, MySolarBattery, MySolarDivert, Solar template, Storage sim, Solar battery sim, Profile, and the template and blank starting points on the dark panels. Storage sim, Solar battery sim, MyElectric2 and Time of Use 2 were light pages and are now dark. | Done |

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
- Gaps seen in dark: glyphicon sprites stay black (step 6), graph "select a feed" box, feed engine badges (pastel, readable).

### 3. Page conversions

One page per commit. Each starts with a short proposal (layout sketch or mock up) for review, then the build, then a browser check.

1. My Account. Done.
2. Post Process. Done.
3. Sync. Done.
4. Schedule. Done.
5. Email Reports. Done.
6. Network. Done. The setup wizard shares its view and keeps the blue look.
7. Admin pages: info, log, components, update, serial, users. Done.

Colours move to variables as each page is converted.

### 4. App kit

- Shared app stylesheet `Modules/app/Views/css/app-kit.css` from the MyElectricFlow components, on the shared light and dark variables. Energy colours as theme variables. Done.
- MyElectricFlow and Psychrograph onto the kit. Done. Pixel diff: flow view identical. Expected changes: the manual time bar Done button gets a rounded end, checkboxes and config fields follow dark mode, Psychrograph legend.
- CO2 Monitor and UK Grid rebuilt with the kit. Done.
- MyHeatpump onto the kit, as the source of the light blocks (`app-block`, `app-bar`, `app-stats`, `app-option`, `--ec-app-bar-bg`). Done.
- The other apps onto the kit: light apps on the blocks, dark apps on the panels. `dark.css`, `light.css`, `graph.css`, the MyBoiler stylesheet copy and the Octopus `tariff_explorer.css` removed. Done 27 September 2026.
- MyElectric2 and Time of Use 2 moved on to the dark panels at Trystan's request.
- Checked in the browser: every app with a test instance (MyHeatpump, MyBoiler, MyElectric2, Time of Use 2, MyEnergy, MySolar, MySolarBattery, and Solar battery sim with faked feed data). Checked from the markup and static renders only: MyElectric, MySolarDivert, Solar template, Storage sim, Profile, Time of Use, Feed-in, Octopus, Cost Comparison, template and blank. These need a test instance for a live check.
- Fixed on the way: Solar battery sim and MyEnergy Balance charts did not draw under Flot 5 (second y axis not declared).
- `config-nav.php` and `graph-nav.php` in `apps/OpenEnergyMonitor` are no longer included and can be removed.

### 5. Shared components from the page conversions

CSS audit of 26 September 2026. Hand written CSS is about the same size on `bootstrap5` as on `master`: core 3091 to 2961 lines, all repos about 9400 lines on both sides, vendor and icon files excluded. Style block lines went from 2156 to 662. The theme, panel, group list and app kit are shared and on variables. The page conversions rebuilt the same small components under a page prefix, so this step moves them to the theme.

One core commit for the theme and panel files, then one commit per page repo.

- Tags, done 27 September 2026: Bootstrap `badge px-2 bg-*-subtle text-*-emphasis` in place of a tag component. The feed engine badges (feeds and sync), admin `info-tag` and `cmp-proto`, users `user-tag` and `user-avatar` colours, and backup `bk-badge` moved to it, after network `net-tag` and the API `badge-*`. The theme adds `--ec-purple-*` and `--ec-orange-*` in both sets with `bg-purple-subtle`, `text-purple-emphasis`, `bg-orange-subtle` and `text-orange-emphasis`. Avatars use six colours, down from eight (teal and pink dropped). Colours now follow the theme's subtle and emphasis values, close to the old pairs. Backup tags lose the pill shape and border.
- Sticky list toolbar, done 27 September 2026: `list-toolbar` and `list-toolbar-sentinel` in `group-list.css`, flex with a 4px gap, and `list_toolbar()` in `Theme/js/emoncms.js` in place of four copies of the observer script. Feeds, inputs, devices and sync use it; their 25 line blocks, the float rules on the filters and the old `.controls` and `.controls.affix` rules in `emoncms-base.css` are removed. Sync now follows the top menu when it hides on phones (`--list-top`, was `--feed-top`). Devices' New device button sits at the right edge, 4px further right, as the filters do.
- Panel form, done 27 September 2026: `panel-form`, `panel-field`, `panel-buttons`, `panel-actions`, `panel-empty`, `is-editing` on a table row and `panel-table` in `panel.css`. The `admin-`, `pp-`, `sch-` and `er-` copies and the users, components, schedule and post process scroll rules are removed. Pixel identical.
- Page defaults, done 27 September 2026: `[v-cloak]`, `page-lead` and `panel-page` in the theme. `panel-page` sets the page background and 1150px width through `:has()`, the bottom padding and the 1rem panel margin. Admin pages, users, post process, schedule, email reports, My Account, sync, backup and graph use it, and their copies (and the API, Network and dashboard list `[v-cloak]`) are removed. My Account (980px), backup (1000px) and sync (full width) keep their width. Changes: users page 40px taller at the bottom and the backup alert under the last panel 8px lower, from the shared padding and margin.
- `.btn { margin: 0 }` resets removed, done 27 September 2026: 20 across 10 files, 7 of them with the panel form. Nothing sets a button margin except Bootstrap's joined `btn-group` and the modal footer, so `btn-group` buttons now overlap by the border width as Bootstrap intends. One reset hid a dead `margin-bottom` on the schedule Add rule button, which is removed too.
- Tokens, done 27 September 2026: unused `--s1` to `--s6`, `--spacer`, `--font-heading`, `--font-base`, `--accent-hover`, `--accent-bg-hover`, `--accent-border`, `--focus-ring`, `--controls-bg`, `--border-card` and `--color-cat-default` removed. Pages use the `--bs-*` and `--ec-*` names: `--accent` to `--bs-primary`, `--accent-bg` to `--bs-primary-bg-subtle`, `--bg-card` to `--bs-body-bg`, `--text-primary` to `--bs-emphasis-color`, and `--ec-*` renames for the values Bootstrap has no match for (see Palette and tokens in `bootstrap5-migration.md`). `--text-muted` stays apart from `--bs-secondary-color` as `--ec-text-muted`, since merging lightens light mode text from `#666` to `#999`. The aliases are removed. Pixel identical.
- Apps: done 27 September 2026. `utils.css` removed: energy colour classes in the kit in place of the redefined Bootstrap `text-*` classes, `app-top-bar` sets its own flex layout, the description `.lead` moved to `appconf.css`. `app.css` is now the Available apps page stylesheet only (with its `<style>` block), no longer loaded on app views; its unused Bootstrap 4 helpers and the `in_kw` rule, which shrank kW values in the MySolar apps, removed. The config panel is rebuilt on the shared dark set in the API and Network look (27 September 2026).
- Apps on Bootstrap components, done 27 September 2026: tabs `nav-underline`, text and icon buttons `nav-link`, time bar `btn-group app-timebar`, actions `btn-outline-primary` (tinted, in the theme), fields `input-group`, checkboxes `form-check`, tables `table`. The kit's `btn-list`, `app-tabs`, `app-btn`, `cost-btn`, `visnavblock`, `visnav`, `ctrl-*` and `app-table` are removed (kit 17281 to 11900 bytes). MyElectricFlow was checked A/B first.
- Reference pages on Bootstrap (27 September 2026): pastel tags to `badge` with subtle utilities, buttons to `btn-default` and `btn-primary`, fields to `form-control`, `form-select` and `input-group`, muted text to `text-body-secondary`, monospace to `font-monospace`, readiness bar to `progress`. The three sheets went from 24383 to 18149 bytes and 14 `!important` went. Fields and buttons now use the theme's compact size, where the API page had its own taller ones. The setup wizard keeps its blue look through overrides of the `--bs-*` and `--ec-*` variables on `.net-blue`.
- Reference page components in the theme, done 27 September 2026: page, title, card on Bootstrap `card`, card title, section heading, label, icon circle, row, code block. API, Network and the config panel on them, and on the `--bs-*` variables in place of the `--accent`, `--bg-card` and `--text-*` aliases. The three page sheets went from 18149 to 10922 bytes, with 3259 bytes added to the theme.
- App kit candidates, done 27 September 2026: `app-stat-sub` and the flow box fills `statsbox-solar`, `-import`, `-export`, `-battery` and `-house` moved to the kit. MyElectricFlow, My Solar PV Battery and My Solar PV Divert use the classes in place of three copies of ID rules, and MyElectricFlow toggles the solar and battery fill class in place of setting hex colours from JS. Arrow colours stay per app, as each app maps flows differently. The two column block row and the small `app-bar` field have one use each and stay in their app files.
- Done 27 September 2026: `panel-badge` works outside a panel header, so the feed edit modal loses its style block. The API explorer and API key cards have no inline styles: Bootstrap flex and gap utilities where the value matches, small classes in `api_explorer.css` where it does not. The My Account colour swatches carry their `theme-*` or `sidebar-*` class and show `--bg-menu-top` or `--bg-l2`, in place of hex copies; the copper swatch now shows the real menu colour (`#e97b00`, was `#e28743`).

Check: pixel diff on the converted pages, as the moves do not change the look.

### 6. Glyphicons to SVG icons

Low priority, last.

- `svg-icons.css` already has 82 icons drawn with CSS masks, in the text colour. The glyphicons have 244 uses, 82 names, across core and the module repos, in markup, JS strings and Vue class bindings.
- Map each glyph name to an SVG icon, adding missing ones. A converter script does the renames.
- Remove `bootstrap2-icons.css` and the sprites in `Theme/img/`.
- Converted pages already use SVG icons.
- Check: icons change shape, so a browser check of the main pages. `icon-white` cases need a text colour instead.

### Release

The `bootstrap5` branch spans core and eleven module repos and is not pushed. It removes Bootstrap 2, `bootstrap2-legacy.css` and the old date picker, so third party modules that use Bootstrap 2 classes need changes. Release after the page conversions (step 3), with notes for module authors from `bootstrap5-migration.md`, and test on an emonSD image. Merge order: modules first, core last, in one release.

### Later

- Site wide light or dark theme as a user setting, next to theme colour. Needs the remaining fixed colours in page CSS moved to variables.
  Files not yet on variables, largest first: demandshaper, timeofuse2, the profile app, graph view error and editor colours, device dialog, dashboard widget and editor CSS, config `style.css`, `autocomplete.css`. MyHeatpump and MyBoiler share one copied stylesheet.
- Colour schemes also set the primary colour.
- Inline `style` attributes and `!important`: tidy when a page is converted, not as a sweep.
- HTML docs section with a style guide.

## Decisions

- Page title: `h3`.
- Apps keep their own light or dark choice for now. A site wide light or dark theme may come later.
- The API pages and MyElectricFlow converge on one dark style, built as the dark version of the shared variables.
- No component page in emoncms for now. This guide is the reference. An HTML docs section with a style guide may come later.
