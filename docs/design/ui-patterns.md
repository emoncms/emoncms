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

- Hand written CSS gets smaller. A page copy moved into the theme counts, as the page loses more than the theme gains. Minifying does not, which is why rules, selectors and declarations are counted next to the bytes.
- Bootstrap use is the share of `bootstrap.min.css` matched at least once across the page state list, from browser CSS coverage. It sets the size of the tree shaken build. Baseline still to record from a logged in run. The same run gives the used share of each hand written file, which points at dead rules.
- Fixed colours outside `bootstrap5-theme.css` go to zero, apart from the exceptions in the principles: 450 hex literals.
- `!important` gets rarer: 69 uses.
- Every theme variable has a use. The unused ones are listed in roadmap step 5.

```sh
sh scripts/bootstrap5/cssbytes.sh
python3 scripts/bootstrap5/csscount.py
node states.mjs cov-input input    # one folder per group, see the scripts README
node csscov.mjs cov-*
```

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

Apps share a kit taken from MyElectricFlow: `Modules/app/Views/css/app-kit.css`, on the shared light and dark variables. MyElectricFlow, Psychrograph, CO2 Monitor and UK Grid use it. The other apps still use the older `Modules/app/Views/css/dark.css` or `light.css`, which retire once the apps have moved.

An app loads the kit with `load_css`, wraps its view (app block, config and loader) in `div.app-page` and sets `data-bs-theme="dark"` on it for the dark look. App specific CSS goes in a file beside the app.

```html
<div class="app-page" data-bs-theme="dark">
    <section id="app-block" style="display:none">
        <div class="app-panel">
            <nav class="app-top-bar d-flex justify-content-between">
                <ul id="tabs" class="btn-list app-tabs">...</ul>
                <ul class="btn-list">config-open and config-close buttons</ul>
            </nav>
            <div class="stats-grid">...</div>
        </div>
        <div class="app-panel">time bar and chart</div>
    </section>
    appconf include, ajax-loader
</div>
```

| Component | Classes |
|---|---|
| App frame | `app-page`, `app-panel` rounded blocks. The first panel has a top margin. |
| Top bar and tabs | `app-top-bar`, `btn-list`, `app-tabs` with `app-btn` tabs (icon and label, accent underline when `active`) |
| Buttons | `app-btn` text button, `cost-btn` outlined accent button, `active` for the chosen option |
| Live values | `stats-grid` of `power-title`, `power-value`, `power-unit`. Colour with `text-primary` (use), `text-warning` (solar), `text-danger` (import), `text-quaternary` (battery). |
| Time bar | `visnavblock` with `visnav app-btn` buttons, joined with rounded ends. Manual date range: `ctrl-group` fields in a second `visnavblock`. |
| Label and field pair | `ctrl-group` > `ctrl-label` + `select` or text `input`, `ctrl-checkbox`, `ctrl-note` |
| Flow blocks | `statstable` of `statsbox` cells: `statsbox-title`, `statsbox-value`, `statsbox-units`, `statsbox-prc`, arrows `statsbox-arrow-down`, `-right`, `-left` in `--statsbox-color`. `statsbox-energy` on a box filled with an energy colour. |
| Tables | `app-table`, `col-primary` for the name cell, `app-swatch` colour square |
| Charts | Flot 5 legend panel and tick labels follow the mode inside `.app-page` (`dark.css` sets the same for older apps). Tick labels are SVG text, so a `font` option needs `fill`. Unlabelled series need `label: ""` to stay out of the legend. Tooltip classes `tooltip-title`, `tooltip-value`, `tooltip-units` keep fixed colours, as the tooltip is added to `body`. |

App colours:

- Surfaces, text, borders and accent come from the shared light and dark variables, plus `--ec-app-panel-bg` and `--ec-app-box-bg`.
- Time bar blue: `--ec-app-nav` and `--ec-app-nav-rgb`.
- Energy colours shared by all apps: `--ec-energy-use`, `--ec-energy-use-light`, `--ec-energy-solar`, `--ec-energy-import`, `--ec-energy-export`, `--ec-energy-battery`, and `--ec-energy-text` for text on them. Values from MyElectricFlow. Heat is added with MyHeatpump. Chart series colours are still set in each app's JS.
- The page background and footer sit outside `.app-page`, so the kit sets them to fixed dark values when a dark `.app-page` is on the page.

### Reference

The API pages are dark with `data-bs-theme="dark"` on `.api-page` and `.api-explorer`, using the shared dark set. Their CSS is in `Lib/api_explorer.css`. The page background stays a fixed `#222` until the colour mode is set on `<html>`.

The Network page uses the same look with its own classes (`net-*` in `network_view.css`): rounded rows with an icon circle, pastel tags and mono IP addresses in one 800px column. The setup wizard shares the markup and turns blue with `net-blue`, a block of variable overrides.

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
| Network | Blue page with white boxed rows | Done: API page look, dark. One column as the original: connection rows (Ethernet, WiFi, Hotspot) with state tags, choice rows, then WiFi networks as rows with signal bars that open in place for the password. Close on the list, a failed state after 60 s without an IP address. The setup wizard shares the markup in blue (`net-blue`). | Done |
| Admin pages | Mixed | Done: page header with actions (`page-actions`) on every page. Info: one panel per section with compact `panel-row` rows, status dot and state tag per service, coloured service buttons as before, branch tag, component chips with LC tag, usage bars green, amber or red, Pi Control with the danger accent. Update: Full Update and Update Database as rows with blue `btn-primary` buttons (an exception to one primary per panel), firmware form panel, update log panel. Update Emoncms Only removed: use Full Update or the Components page. Components: panel table, Update all (each component on its current branch) and bulk branch switch in the header (Stable green, Master orange, Custom red, as before), rows grouped by install folder, one line per component, Source badge (HTTPS purple or SSH amber, as the feed engine badges) that links to the repo. Log and Serial: log window in a panel, serial device settings in panels. Users: page header, CSS in a file, initials avatar in a pastel colour per username, Admin, Verified and feed count tags as the feed engine badges. Sort by clicking a column head, search in the panel header. Shared CSS in `Modules/admin/static/admin_styles.css`. | Done |
| API pages | Dark reference | Done: CSS in a file, colours from the shared dark set. | Done |
| MyElectricFlow, Psychrograph | Dark app, own CSS | Done: source of the app kit, onto it without a visible change. Psychrograph legend fixed (dark panel, no "Plot N" entries). | Done |
| MyHeatpump | Light app | Move to the kit's light set. | Medium |
| CO2 Monitor, UK Grid | Older `dark.css` | Done: rebuilt with the app kit. CO2 Monitor: top bar, time bar and chart, sensor panel with Average and Decay buttons, totals (volume, mean CO2, air change rate) beside the daily CO2 addition field, sensor table. UK Grid: Fuel mix and Forecast tabs, time bar, series toggles as a row above the chart, source notes panel. | Done |
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
- MyHeatpump onto the light set.
- The other apps that load `dark.css` or `light.css` move one by one. The 14 without test instances need instances first. Then `dark.css` and `light.css` are removed.

### 5. Shared components from the page conversions

CSS audit of 26 September 2026. Hand written CSS is about the same size on `bootstrap5` as on `master`: core 3091 to 2961 lines, all repos about 9400 lines on both sides, vendor and icon files excluded. Style block lines went from 2156 to 662. The theme, panel, group list and app kit are shared and on variables. The page conversions rebuilt the same small components under a page prefix, so this step moves them to the theme.

One core commit for the theme and panel files, then one commit per page repo.

- Tag: one component in the theme with colour tokens in both sets (blue, green, amber, purple, red, orange). Replaces the feed engine badges, admin `info-tag` and `cmp-proto`, users `user-tag` and `user-avatar`, network `net-tag`, backup `bk-badge` and the API `badge-*`. The same six colour pairs are pasted into three files today.
- Sticky list toolbar: one class in `group-list.css`. Replaces the 25 line blocks in feeds, inputs, devices and sync, and the old `.controls.affix` rules in `emoncms-base.css`.
- Panel form: `panel-form`, `panel-field`, `panel-buttons`, `panel-actions`, `panel-empty` and `is-editing` on a table row in `panel.css`. Replaces the `admin-`, `pp-`, `sch-` and `er-` copies and the narrow screen table scroll rule.
- Page defaults in the theme: `[v-cloak]`, body background, container width, page bottom padding and `page-lead`. Panel margin 1rem, which every page sets.
- Remove the `.btn { margin: 0 }` resets, 19 across 8 files. Nothing sets a button margin.
- Tokens: remove the unused `--s1` to `--s6`, `--font-heading`, `--font-base`, `--accent-hover`, `--accent-bg-hover`, `--focus-ring`, `--controls-bg`, `--color-cat-default` and `--ec-energy-export`. Decide between `--text-muted` and `--bs-secondary-color`, and whether pages use the `--bs-*` and `--ec-*` names or the `--accent`, `--bg-card` and `--text-*` aliases.
- Apps: `app.css` (loaded by the controller), `utils.css` (loaded by the config panel) and `app-kit.css` overlap. One shared file once the apps are on the kit. `appconf.css` onto the variables so the config panel follows the mode.
- Feed edit modal `panel-badge` style block, API explorer inline layout styles and the profile page swatch colours onto the theme.

Check: pixel diff on the converted pages, as the moves do not change the look.

### 6. Glyphicons to SVG icons

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
  Files not yet on variables, largest first: demandshaper (402 lines, 51 literals, still on master), timeofuse2, the profile app, graph view error and editor colours, device dialog, dashboard widget and editor CSS, config `style.css`, `autocomplete.css`. MyHeatpump and MyBoiler share one copied stylesheet.
- Colour schemes also set the primary colour.
- Inline `style` attributes and `!important`: tidy when a page is converted, not as a sweep.
- Tree shake Bootstrap. `bootstrap.min.css` is 232 KB (31 KB gzipped) with 2012 class names. The pages reference 234 of them (measured 26 September 2026 by class name across core and the module repos, so an over count). Unused: carousel, offcanvas, toast, popover, breadcrumb, placeholder, most of card, list group, navbar, and most of the utility and grid sets. The JS bundle is used for modal, dropdown, collapse, tooltip and button toggle. Build from the Bootstrap Sass with only the needed imports, in `scripts/bootstrap5/`, rather than purging the dist file: classes added by JS (`show`, `fade`, `collapsing`, `modal-backdrop`) and built in Vue templates and JS strings are easy to purge by mistake. Utilities through the Sass utility API, listing the ones in use. Keep the dist file until a pixel diff of the page list passes.
- HTML docs section with a style guide.

## Decisions

- Page title: `h3`.
- Apps keep their own light or dark choice for now. A site wide light or dark theme may come later.
- The API pages and MyElectricFlow converge on one dark style, built as the dark version of the shared variables.
- No component page in emoncms for now. This guide is the reference. An HTML docs section with a style guide may come later.
