# Emoncms CSS guide

How to style pages in emoncms core and modules. Emoncms uses Bootstrap 5.3 with an emoncms theme and a small set of shared components. New pages and modules start from the patterns here.

Converting Bootstrap 2 code: see `bootstrap5-migration.md`.

Contents:

1. Overview
2. Principles
3. Adding CSS to a module
4. Colours and tokens
5. Light and dark
6. Base styles
7. Page families
8. Setup page components
9. Reference pages
10. Apps
11. Bootstrap build
12. Planned

## 1. Overview

Every page loads, in this order (`Theme/theme.php`):

| File | Contents |
|---|---|
| `Lib/bootstrap5/css/bootstrap.min.css` | Bootstrap 5.3.8, a reduced build (see Bootstrap build) |
| `Theme/css/bootstrap5-theme.css` | Palette and tokens, element look, buttons, component sizes, page shell, reference page components |
| `Theme/css/bootstrap2-icons.css` | Glyphicons (`icon-*`) |
| `Theme/css/emoncms-base.css` | Colour schemes, sidebar sets, shell details |
| `Theme/css/menu.css` | Top menu and sidebar |
| `Theme/css/panel.css` | Panel component |
| `Theme/css/group-list.css` | Group list and sticky list toolbar |
| `Theme/css/autocomplete.css` | Autocomplete fields |
| `Theme/css/svg-icons.css` | SVG icons (`svg-icon-*`) |

JS: jQuery, `Theme/js/emoncms.js` (helpers such as `list_toolbar()`) and `Lib/bootstrap5/js/bootstrap.bundle.min.js` (Bootstrap with Popper, at the end of the page). Bootstrap plugins have a jQuery bridge, so `$(el).modal('show')` works.

Apps also load `Modules/app/Views/css/app-kit.css`.

## 2. Principles

- Use a Bootstrap class or a shared component before writing page CSS. A new shared component goes in this guide.
- Colours and shared values come from the variables in `bootstrap5-theme.css`, which have a light and a dark set. Page and app CSS do not set fixed colours. Exceptions: a component that sets both its background and its text, such as a pastel badge, and colours on the coloured top bar or the log window.
- Colours carry meaning: green is fresh or OK, orange is a warning, red is stale or destructive. An energy source has the same colour in every app.
- Page CSS goes in a `.css` file, not a `<style>` block. Inline `style` attributes only for values set from JS.
- Keep CSS small. Remove a rule when its markup goes.
- Page titles are `h3`.

## 3. Adding CSS to a module

- Load page CSS and JS with `load_css()` and `load_js()` from `core.php`. The loaders add the file time to the URL, so browsers fetch the new file after an update. Do not use `<link>` or `<script>` tags with a fixed `?v=`.
- Put the stylesheet beside the view, for example `Modules/sync/sync_view.css`.
- Prefix page classes with a short page name (`net-`, `bk-`, `cfg-`), so they do not clash with Bootstrap or other pages. Avoid Bootstrap names such as `modal-content` or `accordion-body` for page classes.
- Read colours from variables: `var(--bs-primary)`, `var(--bs-border-color)`, `var(--ec-text-muted)`. A new shared colour goes in both sets of the theme as `--ec-*`.
- A Bootstrap class that is new to the codebase may be missing from the reduced build. Run `node scripts/bootstrap5/build.mjs --check` and rebuild if it lists the class (see Bootstrap build).

## 4. Colours and tokens

All colours and shared values are at the top of `bootstrap5-theme.css`, in three blocks. Components read them, so a look change is an edit there.

| Block | Contents |
|---|---|
| `:root, [data-bs-theme]` | Values shared by both modes: button colours, type, shape, spacing, energy colours, and aliases such as `--bs-link-color`. On every theme root, so aliases take the colours of that root's mode. |
| `:root, [data-bs-theme="light"]` | Light set |
| `[data-bs-theme="dark"]` | Dark set. Primary `#44b3e2`, surfaces `#2e2e2e`, text `#ccc`, borders `#3f3f3f`. |

The light and dark sets declare the same variables. A new colour goes in both.

Use the Bootstrap name where the role is the same, and the `--ec-*` name otherwise.

| Role | Variable |
|---|---|
| Accent (links, primary buttons, focus, active items) | `--bs-primary`, `--bs-primary-rgb`. `#2a8fc7` light, `#44b3e2` dark. |
| Accent hover and border, active | `--ec-primary-dark`, `--ec-primary-darker` |
| Accent tint | `--bs-primary-bg-subtle` |
| Text | `--bs-body-color`, `--bs-emphasis-color` (strong), `--ec-value-color` (values and table cells) |
| Secondary and muted text | `--ec-text-secondary`, `--ec-text-muted`, `--bs-secondary-color` |
| Surfaces | `--bs-body-bg`, `--bs-secondary-bg`, `--bs-tertiary-bg` |
| Borders | `--bs-border-color`, `--ec-input-border-color`, `--ec-divider` |
| Panel and group list headers | `--ec-header-bg`, `--ec-header-hover-bg` |
| Row hover | `--ec-row-hover-bg` |
| Group list | `--ec-list-row-bg`, `--ec-list-selected-bg`, `--ec-list-selected-hover-bg`, `--ec-list-status` |
| State colours | `--bs-<colour>-rgb` for text, `--bs-<colour>-text-emphasis`, `-bg-subtle` and `-border-subtle` for alerts, row tints and tags |
| Coloured buttons | `--ec-btn-<colour>`, `-dark`, `-darker`; default button `--ec-btn-default-*` |
| Code | `--bs-code-color`, `--ec-code-bg`, `--ec-code-border` |
| Other components | `--ec-highlight`, `--ec-highlight-soft` (inset line and text shadow, transparent in dark), `--ec-table-striped-bg`, `--ec-form-text` |
| Energy (apps) | `--ec-energy-use`, `-use-light`, `-solar`, `-import`, `-export`, `-battery`, `-direct`, `-wind`, `--ec-energy-text` for text on them |
| App surfaces | `--ec-app-panel-bg`, `--ec-app-box-bg`, `--ec-app-bar-bg`, time bar blue `--ec-app-nav-rgb` |
| Extra pastel pairs | `--ec-purple-*`, `--ec-orange-*` |

`--border`, `--bg-body`, `--font-*` and `--radius-*` are also available.

Colour schemes: `emoncms-base.css` holds the scheme classes (`.theme-*`), which set the top menu colours as `--bg-menu-top` and related variables, and the sidebar sets (`.sidebar-dark`, `.sidebar-light`). A component that should follow the scheme reads these, as the sticky list toolbar does with `--bg-menu-top-active`.

## 5. Light and dark

Colour mode uses the Bootstrap 5.3 attribute `data-bs-theme`.

- A page or app turns dark with `data-bs-theme="dark"` on its root element. Bootstrap and emoncms components inside it follow the variables, so they work in both modes.
- `[data-bs-theme]` also sets the text colour, as Bootstrap sets it on `body` only.
- The API pages, Network, the app config panel and the dark apps set `dark`. Light apps set `light`. Setup pages use the default light set.
- The page background and footer sit outside the page root. The app kit and the reference pages set them to fixed dark values when a dark root is on the page.
- To check a component in both modes, render it inside a `data-bs-theme="dark"` wrapper.

## 6. Base styles

The theme keeps the emoncms look where the Bootstrap 5 defaults differ.

Elements:

- Headings bold with 10px margins and the emoncms sizes. Links underlined on hover only. 10px paragraph margin.
- Lists indent by a 25px margin with no padding. `.nav` has no margin.
- `dl` top margin, `dd` bottom margin, `legend` float and `hr` opacity as before the reboot. `code` and `pre` styled from the tokens.
- The link hover rule uses `a:where(:hover, :focus)`, so `.btn`, `.nav-link` and `.dropdown-item` keep no underline.
- Table cells and links without `href` inherit their colour.

Change the element look in the theme base section, not with utilities on each page.

Differences from stock Bootstrap 5:

- `form-control` and `form-select` are compact: 14px text, 4px 6px padding. Width classes set fixed widths (see Forms).
- `.input-group` is inline and sized to its content. A full width group needs `d-flex w-100`.
- Buttons: `btn-default` is the plain grey button. `btn-xs` is an extra small size. Coloured buttons use the emoncms palette. `btn-outline-primary` is a tint of the accent.
- Badges: `badge` has the compact label look. Linked badges (`a.badge[href]`) are darker.
- Tables have the emoncms cell padding and borders, and row tints (`table-success` and the like) without stripes.
- Alerts have the emoncms padding.
- Modals are 560px wide, 10% from the top, body capped at 400px with scrolling, grey footer, full width with a 20px margin below 768px.
- `hide` sets `display: none` without `!important`, so jQuery `.show()` can undo it. Bootstrap's `d-none` cannot be undone from jQuery.
- `main.content-container` has `display: flow-root` and `width: auto`, as it sits beside the sidebar.

Bootstrap 5 points to keep in mind:

- Every element is `box-sizing: border-box`. A width or height includes padding and border. Use `box-sizing: content-box` on a rule that needs the width to exclude them.
- Utilities are `!important`. A page rule cannot override them.
- `.row > *` gets gutter padding. Use `row g-0` for columns with their own padding.
- `.btn-group > .btn` is `flex: 1 1 auto`. Stop buttons stretching with a two class selector. Add `text-nowrap` where buttons in a narrow group would wrap.
- `.dropdown-toggle` draws a caret with `::after`. Hide it where the design has none.
- `.badge:empty` is hidden. A badge used as a dot needs `display: inline-block`.
- `btn-link` is underlined.

## 7. Page families

| Family | Purpose | Look | Examples to copy |
|---|---|---|---|
| Setup pages | Managing things and settings | Light, grey and white, in the emoncms shell | Inputs, Feeds (lists), My Account, Post Process, Schedule, Admin (panels) |
| Apps | Dashboards for the household | Dark or light, chosen per app | MyElectricFlow (dark), MyHeatpump (light) |
| Reference | API documentation, network setup | Dark | API help pages, Network |

The login page (`Modules/user/login_block.php`) sits outside the families. It is a light card on `--bg-body-login`, with the logo in a header in `--bg-menu-top`, so it follows the colour scheme.

A setup page uses one of two layouts under a page header.

**List layout.** For pages that list things: feeds, inputs, devices. Rows grouped by node or tag, collapsible, with selection and a sticky toolbar. Examples: Inputs, Feeds, Devices, Sync.

**Panel layout.** For settings, forms and tools. Examples: My Account (key and value rows with inline edit), Post Process and Schedule (panel table and panel form), Email Reports (tabs and switches), Admin info (compact rows, status tags), Backup, Graph.

## 8. Setup page components

### Page header

`h3` title on the left, actions and the help link on the right.

```html
<div class="page-header">
    <h3>Feeds</h3>
    <a href="feed/api">Feed API Help</a>
</div>
```

`page-lead` for a line under the header.

### Panel page

`panel-page` on the page root: grey page background, content 1150px wide, bottom padding, 1rem between panels. A page that needs another width sets `max-width` on `main.content-container:has(.its-page)`. Panels can sit in a Bootstrap grid (`row g-3` > `col-lg-6`).

### Panel

`Theme/css/panel.css`.

```html
<div class="panel">
    <div class="panel-header panel-header-static">
        <span class="panel-accent"></span>
        <span class="panel-name">API keys</span>
    </div>
    <div class="panel-row">
        <div class="row-key">Read key</div>
        <div class="row-value">...</div>
        <span class="row-action svg-icon-content_copy" title="Copy"></span>
    </div>
</div>
```

Rows sit straight in the panel. Other content goes in `panel-body`.

| Class | Role |
|---|---|
| `panel` | Container |
| `panel-header` | Header row, pointer and hover |
| `panel-header-static` | Header that only labels the panel, no pointer or hover |
| `panel-accent` | Accent bar before the name, `panel-accent-danger` for destructive actions |
| `panel-name` | Title |
| `panel-badge` | Count or state beside the name. Also works outside a panel header. |
| `panel-body` | Content with padding |
| `panel-controls` | Strip of fields and buttons |
| `panel-row` | Key and value row: `row-key`, `row-value`, `row-note`, then `row-action` icons and buttons. `is-editing` while an inline edit is open. |
| `row-action` | Icon action, shown on row hover and always on touch screens |
| `panel-form` | Stacked form in the panel body: `panel-field` blocks with a `form-label` above the field, then `panel-buttons` |
| `panel-empty` | Message in place of an empty table |
| `table` in a panel | Uppercase grey column heads, row hover. `col-primary` and `col-secondary` on cells, `panel-actions` on the buttons cell, `is-editing` on the row open in the editor. |
| `panel-table` | Wrapper for a table that scrolls sideways. The page sets the table `min-width` on narrow screens. |

Inline edit replaces the value with the field and Save and Cancel buttons.

### Group list

`Theme/css/group-list.css`. Column widths are set per page on the `group-list` grid, with `data-col` on each cell.

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

Status: `--status-color` on a header or row sets the stripe on its right edge. Time since update in green or red text.

### Sticky list toolbar

`div.list-toolbar` with `btn btn-default` icon buttons, then the filter field or page actions pushed right with `ms-auto`. An empty `div.list-toolbar-sentinel` goes above it. Once both are rendered, the page calls:

```js
list_toolbar(sentinel, '.list-toolbar');
```

The toolbar sticks under the top menu with a bar in the menu colour (`is-sticky`).

### Buttons

- `btn-default` for ordinary actions, `btn-primary` for the main action of a panel or modal, `btn-danger` for delete and other destructive actions.
- One primary button per panel or modal.
- `btn-sm` and `btn-xs` for smaller buttons. Keep a variant class on every `btn`, including when JS swaps variants.
- Icon buttons: `btn btn-default` holding an `svg-icon-*`.

### Labels and tags

- Status labels: `badge bg-success`, `bg-warning`, `bg-danger`, `bg-secondary`.
- Pastel tags (method, access, state): `badge px-2 bg-success-subtle text-success-emphasis`, with `primary`, `secondary`, `info`, `warning`, `danger`, `purple` or `orange` in place of `success`. No page CSS is needed. The theme sets the subtle and emphasis colours in both modes and adds the purple and orange utilities.
- Examples: feed engine badges, admin tags, user avatars, API method tags.

### Forms

Every text input, select and textarea has `form-control` or `form-select`. A bare field shows as a plain browser field.

| Need | Markup |
|---|---|
| Field with a fixed width | `form-control input-220`, `form-select input-165` |
| Widths | `input-75`, `input-105`, `input-165`, `input-220`, `input-285`, `input-545`, `input-auto` (select sized to its options) |
| Full width | `form-control` alone |
| Label above | `label.form-label` (block, 5px below) |
| Help text | `form-text` |
| Label or unit beside a field | `input-group` > `input-group-text` + field. The field needs a width class. |
| Stacked fields | `mb-2` on each |
| Invalid | `is-invalid` on the field, `text-danger` on the help text |
| Colour | `form-control form-control-color` |
| On and off setting | `form-check form-switch` |
| Checkbox and radio | Native field, drawn in the accent colour with `accent-color` |
| Monospace | `font-monospace` |

- Width classes set the total width (padding and border included), `display: inline-block` and `vertical-align: middle`. They apply only together with `form-control` or `form-select`.
- `input-285` and `input-545` go full width below 768px, except in an input group.
- Readonly fields keep the grey background.
- A page rule that sets a select's height must set line height to the height less padding and border, or the text sits low.
- A page rule such as `.x input[type=text]` also reaches fields inside components such as the date picker. Use a child selector.

### Date picker

`Lib/js/DateTimePicker.js` with `Theme/css/datetimepicker.css`.

- Vue: `<date-time-picker v-model="start" @change="reload">` inside an `.input-group`. It renders an input, a calendar button and a dropdown menu as children of the group.
- Other pages: `DateTimePicker.attach(input, { value, onChange, buttonClass })` adds the button and menu after an existing input in an `.input-group`. The input keeps its id, value and events, and gets a `change` event when a date is applied. Returns `getDate()` and `setDate(date)`. `setDate` does not call `onChange`.
- Values are local time, `YYYY-MM-DD HH:MM:SS`. `DateTimePicker.parse` and `DateTimePicker.format` convert to and from `Date`.
- The menu uses Popper with fixed positioning, so a scrolling modal body does not clip it.

### Modals

```html
<div id="x" class="modal" tabindex="-1" aria-labelledby="xLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="xLabel" class="modal-title">Title</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">...</div>
        </div>
    </div>
</div>
```

- Open and close with `$(el).modal('show')` and `$(el).modal('hide')`. Events are `shown.bs.modal` and `hidden.bs.modal`.
- `.modal` is the full screen overlay and `.modal-dialog` the box. Set the width with `--bs-modal-width` on the modal. Position and margin go on `.modal-dialog`, border and radius on `.modal-content`.
- JS that reads the box position reads `.modal-content`.

### Other Bootstrap JS

- Collapse: `data-bs-parent` goes on the `.collapse` element, not on the toggle.
- `data-bs-toggle="button"` toggles `active` before a page click handler runs, so the handler sees the new state.

### Icons

- New code uses SVG icons: `<span class="svg-icon-wrench"></span>`. They are CSS masks in the text colour, so they follow the mode and the button colour. The set is in `Theme/css/svg-icons.css`. A missing icon is added there.
- Glyphicons (`icon-*`, `icon-white`) still work but are to be replaced (see Planned).

## 9. Reference pages

The API pages, the Network page and the app config panel share one set of components, section 5 of `bootstrap5-theme.css`, on Bootstrap cards and the dark set. Each page sets `data-bs-theme="dark"`.

| Component | Classes |
|---|---|
| Page | `ref-page`, width from `--ref-width` (1040px default; API 1150px, Network 800px) |
| Title | `ref-head` with `h2` and a lead `p` in the first child, actions after it |
| Card | Bootstrap `card`, or `card card-body` for content. Radius, spacing and colours are set on `.ref-page .card`. `open` gives the accent border. Two cards side by side: `row row-cols-1 row-cols-lg-2 g-3`. |
| Card title | `ref-card-title` with a `ref-icon` |
| Section heading | `ref-section` holding an `h4.ref-label`, then a count or buttons |
| Label | `ref-label`, the uppercase grey label, also for sidebar groups |
| Icon circle | `ref-icon`, `ref-icon-sm` on rows. A state colour with `bg-success-subtle text-success-emphasis` and the like. |
| Row | `ref-row` in a card: icon, text, values, actions. `is-link` for rows that open in place. The open card gets `open`. |
| Code | `ref-code` for URLs, request bodies and responses |
| Tags | `badge px-2 bg-*-subtle text-*-emphasis` |

Page CSS keeps what is particular to the page: the API sidebar, parameter grid and chevron (`Lib/api_explorer.css`), the WiFi signal bars and row parts (`network_view.css`), the feed grid and value cells of the config panel (`appconf.css`). A different colour set is made by overriding the `--bs-*` variables on a wrapper, as the setup wizard does with `net-blue`.

## 10. Apps

Apps share one kit, `Modules/app/Views/css/app-kit.css`, on the shared variables. Dark apps use panels, from MyElectricFlow. Light apps use blocks, from MyHeatpump.

An app loads the kit with `load_css`, wraps its view (app block, config and loader) in `div.app-page` and sets `data-bs-theme="dark"` or `"light"` on it. Light apps also load `Lib/fonts/montserrat/montserrat.css`, which the kit applies to a light `.app-page`. App specific CSS goes in a file beside the app.

Dark app:

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

Light app:

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
| Buttons | Text or toggle button: `nav-link` in a `nav`, underlined when `active`. Action: `btn btn-outline-primary`, `active` for the chosen option. |
| Live values | `stats-grid` (three columns, `stats-grid-2` for two) of `power-title`, `power-value`, `power-unit`. Colour with the energy classes. |
| Energy colours | `text-use`, `text-house`, `text-solar`, `text-wind`, `text-direct`, `text-import`, `text-export`, `text-battery`. Bootstrap `text-*` classes keep their Bootstrap meaning. |
| Time bar | `app-navbar` row (safe to show and hide from JS) with a `btn-group app-timebar` of plain `btn` buttons, then notes and a `nav ms-auto` of `nav-link` toggles such as Daily. Manual date range: `input-group` fields with `DateTimePicker.attach`, and a one button `app-timebar` for Done. |
| Fields | `input-group w-auto` > `input-group-text` + `form-select` or `form-control` (width classes), a trailing `input-group-text` for a unit. `form-check` for a checkbox, `small text-body-secondary` for a note. `.app-page .input-group` has no bottom margin, so fields line up with buttons in a flex row. |
| Flow blocks | `statstable` of `statsbox` cells: `statsbox-title`, `statsbox-value`, `statsbox-units`, `statsbox-prc`, arrows `statsbox-arrow-down`, `-right`, `-left` in `--statsbox-color`. `statsbox-energy` on a box filled with an energy colour, with the fill `statsbox-solar`, `-import`, `-export`, `-battery` or `-house`. |
| Tables | `table` (`table-sm` for dense ones), `col-primary` for the name cell, `app-swatch` colour square |
| Blocks (light apps) | `app-block` > `app-bar` (grey header bar: `app-bar-title`, `app-bar-btn` buttons with `active`, `app-bar-spacer` to push the following buttons right), `app-block-body` (white), `app-block-foot` (grey summary strip) |
| Block values | `app-stats` row of equal columns, each `app-stat-title`, `app-stat-value` with `app-stat-unit`, `app-stat-sub` for a small line below |
| Option rows | `app-option`: checkbox and bold label, with fields (`input-group`) below when ticked. Rows stack with shared borders. |

Compact dark app, from MyElectricFlow. Other dark apps still use panels.

```html
<div class="app-card">
    <nav class="app-card-head">tabs, then app-card-tools: app-status, config nav</nav>
    <div class="app-live">label and value per item</div>
</div>
<div class="app-card app-card-body">app-navbar, chart, app-legend</div>
<div class="app-card app-card-body">app-card-caption, app-flow</div>
```

| Component | Classes |
|---|---|
| Card | `app-card` bordered block, `app-card-body` for padding. `app-card-head` header row with `app-card-tools` on the right. |
| Status | `app-status` > `app-status-dot` + `app-status-text`. `is-live` turns the dot green. |
| Live values | `app-live` row of equal columns, each `app-live-label` (uppercase) and `app-live-value` with `power-unit` or `power-unit-static`. Hidden items give their width to the rest. Three columns on narrow screens. |
| Chart toolbar | `<?php include "Modules/app/Lib/timebar.php"; ?>` in `div#graph-nav.app-navbar`, then `Lib/timebar_manual.php` after it. One `app-timebar` group as the graph module: range `select.btn` (`$timebar_ranges` to change the list), `icon-resize-horizontal` switch to Start and End fields (`icon-ok` back), zoom, pan and Now, then `app-window-text`. `btn-group app-segmented` for a two way toggle. Sprite icons take `icon-white` on dark. JS in `Lib/vis.helper.js`: `timebar_update(daily)` after each draw, `timebar_manual(cb)` and `timebar_now(cb)` once, `live_status_update(time)` for the status, `chart_legend(series)` for a legend below the chart. |
| Legend | `app-legend` of `app-legend-item`, each an `app-legend-swatch` or `app-legend-line` and a label. |
| Caption | `app-card-caption` > `app-section-label` + `app-caption-note` |
| Flow diagram | `app-flow` five column grid of `app-flow-node` (energy fill with `statsbox-solar`, `-import`, `-battery`, `-house`; `app-flow-wide` spans three) and `app-flow-link` (colour from `--statsbox-color`). Content: `app-flow-name`, `app-flow-value`, `app-flow-unit`, `app-flow-title`, `app-flow-arrow`. |

Colours:

- Surfaces, text, borders and accent come from the shared variables, plus `--ec-app-panel-bg`, `--ec-app-box-bg` and `--ec-app-bar-bg`.
- Time bar blue: `rgba(var(--ec-app-nav-rgb), a)`.
- Energy colours: `--ec-energy-*` (see Colours and tokens). Chart series colours are set in each app's JS.

Config panel: `Lib/appconf`, shared by every app and dark in all of them. Header with the app name and Launch app, readiness strip, App and About cards, feeds as two-column rows (status circle, key, node, AUTO, DERIVED or REQUIRED tag, click to edit in place), unused optional feeds behind a Show button, kWh flow feeds card, options as rows with switches, Manage rows. The first `.lead` paragraph of `#appconf-description` becomes the header line. Classes `cfg-*` in `appconf.css`.

Charts: Flot 5 legend panel and tick labels follow the mode inside `.app-page`. Tick labels are SVG text, so a `font` option needs `fill`. Unlabelled series need `label: ""` to stay out of the legend. Tooltip classes `tooltip-title`, `tooltip-value` and `tooltip-units` have fixed colours, as the tooltip is added to `body`.

## 11. Bootstrap build

`Lib/bootstrap5/css/bootstrap.min.css` is built from the Bootstrap 5.3.8 Sass by `scripts/bootstrap5/build.mjs`, then purged against the source of core and the modules. It is not the stock file.

- Left out: navbar, accordion, breadcrumb, pagination, list group, toasts, popover, carousel, offcanvas and placeholders.
- Included in full: the grid at every breakpoint and the utility families without breakpoint variants (`d-*`, `flex-*`, spacing, `w-*`, `h-*`, `text-*`, `bg-*`, `border-*` and similar).
- Included when used: responsive utility variants such as `d-md-flex`, and the classes of each component.

A class that is not in the build has no style. After adding Bootstrap classes:

```sh
cd scripts/bootstrap5 && npm ci
node build.mjs --check  # classes used in the source but missing from the build
node build.mjs          # rebuild, then the same check
```

To use a left out component, uncomment it in `scripts/bootstrap5/scss/bootstrap.scss` and rebuild. Modules outside the emoncms repos are not scanned. If a class they use is missing, ask for it to be added to the build. See `scripts/bootstrap5/README.md`.

## 12. Planned

- Glyphicons to SVG icons. Map each glyph name to an `svg-icon-*`, adding missing icons, then remove `bootstrap2-icons.css` and the sprites in `Theme/img/`. `icon-white` cases need a text colour instead.
- Site wide light or dark mode as a user setting, set on `<html>`. Needs the remaining fixed colours in page CSS moved to variables. Largest first: demandshaper, timeofuse2, the profile app, graph view error and editor colours, device dialog, dashboard widget and editor CSS, `autocomplete.css`.
- Inline `style` attributes and `!important`: tidy when a page is next changed.
- Further work on bringing the different page family styles together into a unified style.
