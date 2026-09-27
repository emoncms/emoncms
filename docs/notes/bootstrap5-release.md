# Bootstrap 5: notes for module authors

Emoncms moves from Bootstrap 2.3.2 to Bootstrap 5.3 in one release. Bootstrap 2 is removed, so a module that uses Bootstrap 2 markup, classes or JS needs changes before it works with this release. All modules maintained by OpenEnergyMonitor are converted.

Full conversion rules: `docs/design/bootstrap5-migration.md`. Page layouts and components: `docs/design/ui-patterns.md`.

## Removed

- Bootstrap 2 (`Lib/bootstrap/`), `Theme/css/bootstrap2-legacy.css` and `Theme/css/bootstrap4-utils.css`.
- bootstrap-datetimepicker 0.0.11. Use `Lib/js/DateTimePicker.js`.
- Theme variables `--accent`, `--bg-card` and `--text-*`, and other old aliases. See Variables below.

Kept: glyphicons (`icon-*`, `icon-white`, from `Theme/css/bootstrap2-icons.css`) and the `hide` class.

## Converting a module

1. Classes. Rename Bootstrap 2 classes in markup, CSS selectors and JS (`addClass`, strings of HTML). Common ones:

   | Bootstrap 2 | Bootstrap 5 |
   |---|---|
   | `btn` with no variant | `btn btn-default` |
   | `btn-small`, `btn-mini` | `btn-sm`, `btn-xs` |
   | `btn-large` | `btn-lg`, after a rebuild (see Bootstrap build) |
   | `label label-x` | `badge bg-x` |
   | `alert-error`, `text-error` | `alert-danger`, `text-danger` |
   | `pull-right`, `pull-left` | `float-end`, `float-start` |
   | `input-prepend`, `input-append`, `add-on` | `input-group`, `input-group-text` |
   | `row` > `spanN` | `row` > `col-N` |
   | `well` | `bg-body-tertiary border rounded p-3 mb-3` |
   | `muted` | `text-muted` |
   | `data-toggle`, `data-dismiss`, `data-target` | `data-bs-*` |
   | `dropdown-menu pull-right` | `dropdown-menu dropdown-menu-end`, links get `dropdown-item` |

2. Forms. Every text input, select and textarea needs `form-control` or `form-select`, with a width class for its size: `input-75`, `input-105`, `input-165`, `input-220`, `input-285`, `input-545` or `input-auto`. A bare field shows as a plain browser field. A field in an `input-group` needs a width class.

3. Modals. Add the `modal-dialog` and `modal-content` wrappers, remove `hide` from `.modal`, use `btn-close` after the title. Open with `$(el).modal('show')`: `$(el).modal()` no longer opens it. Events are `shown.bs.modal` and `hidden.bs.modal`.

4. Box sizing. Bootstrap 5 sets `box-sizing: border-box` on every element. A page rule that sets a width or height together with padding or border now shrinks. Add `box-sizing: content-box` to the rule.

5. Grid. `.row > *` takes full width and gutter padding. Use `row g-0` and `col-*` classes, or a flex layout, in place of hand sized columns.

6. Loading. Load page CSS and JS with `load_css()` and `load_js()`, not `<link>` or `<script>` tags with a fixed `?v=`. The loaders add the file time, so browsers do not keep an old copy after an update.

The scripts in `scripts/bootstrap5/` convert most of this: `cvt_classes.py`, `cvt_modal.py`, `cvt_css.py` and `boxsizing.py`. `formlint.py` lists fields without `form-control`. Review the diff after each run.

## Bootstrap build

`Lib/bootstrap5/css/bootstrap.min.css` is a reduced build, 98 KB where the stock file is 232 KB. Unused components are left out: navbar, accordion, breadcrumb, pagination, list group, toasts, popover, carousel, offcanvas and placeholders. The grid and the utility classes (`d-*`, `flex-*`, spacing, `text-*`, `bg-*`, `border-*` and similar) are all included. Their breakpoint variants, such as `d-md-flex`, are included only when some page uses them.

A class that is not in the build has no style. To add one:

```sh
cd scripts/bootstrap5 && npm install
node build.mjs          # rebuild from the source of core and the modules
node bsmissing.mjs      # list classes used but missing from the build
```

To use a left out component, uncomment it in `scripts/bootstrap5/scss/bootstrap.scss` and rebuild. Modules outside the emoncms repos are not scanned. If a class they use is missing, ask for it to be added to the build.

## Variables

Colours and shared values are in `Theme/css/bootstrap5-theme.css`, in a light and a dark set. Page CSS uses the Bootstrap names where the role is the same and `--ec-*` names otherwise.

| Removed | Use |
|---|---|
| `--accent` | `--bs-primary` |
| `--accent-bg` | `--bs-primary-bg-subtle` |
| `--bg-card` | `--bs-body-bg` |
| `--text-primary` | `--bs-emphasis-color` |
| `--text-body` | `--ec-value-color` |
| `--text-secondary` | `--ec-text-secondary` |
| `--text-muted` | `--ec-text-muted` |
| `--bg-card-header`, `--bg-card-header-hover` | `--ec-header-bg`, `--ec-header-hover-bg` |
| `--bg-card-row-hover` | `--ec-row-hover-bg` |

Also removed without a replacement: `--accent-hover`, `--accent-bg-hover`, `--accent-border`, `--accent-row-hover`, `--focus-ring`, `--controls-bg`, `--border-card`, `--color-cat-default`, `--spacer`, `--font-heading`, `--font-base` and `--s1` to `--s6`. `--border`, `--bg-body`, `--font-*` and `--radius-*` stay.

## New components

Components in the theme that a module can use in place of its own CSS. Details and markup in `ui-patterns.md`.

- `page-header`: page title with actions and help link.
- `panel-page`: grey page background and 1150px content width for panel pages. `page-lead` for the line under the header.
- `panel` (`Theme/css/panel.css`): `panel-header`, `panel-body`, `panel-row` key and value rows with inline edit, `panel-form` stacked forms, `panel-table`, `panel-empty`.
- `list-toolbar`: sticky toolbar above a list, with `list_toolbar()` in `Theme/js/emoncms.js`.
- `group-list` (`Theme/css/group-list.css`): grouped, collapsible lists as on Inputs and Feeds.
- Pastel tags: `badge px-2 bg-success-subtle text-success-emphasis`, with any theme colour, plus `purple` and `orange`.
- `ref-*`: the dark reference page look of the API and Network pages.
- App kit (`Modules/app/Views/css/app-kit.css`): shared components for apps, including energy colour classes `text-solar`, `text-import` and the rest.
- `DateTimePicker`: one date picker for Vue and jQuery pages.
