# Bootstrap 5 migration

Emoncms is moving from Bootstrap 2.3.2 to Bootstrap 5.3. All modules switch in one release. There is no period where both versions are supported.

Aim for the current look first. Visual changes come later, through the theme variables.

## Files

- `Lib/bootstrap5/`: Bootstrap 5.3.8 dist (`bootstrap.min.css`, `bootstrap.bundle.min.js` with Popper).
- `Theme/css/bootstrap5-theme.css`: Bootstrap 2 metrics and colours as Bootstrap 5 variables. Loaded straight after `bootstrap.min.css`.
- `Theme/theme.php`: pages listed in `$bootstrap5` load Bootstrap 5. Remove the list and Bootstrap 2 once every page is converted.
- `Theme/css/bootstrap4-utils.css`: not loaded with Bootstrap 5, which has the same utilities. Custom classes in it (`text-tertiary`, `text-quaternary`, `color-box`, `text-exporting`, `m-6`, `p-6`) need a new home.

## Class conversion

| Bootstrap 2 / bootstrap4-utils | Bootstrap 5 |
|---|---|
| `btn` with no variant | `btn btn-default` |
| `btn-small` | `btn-sm` |
| `btn-mini` | `btn-sm` |
| `btn-large` | `btn-lg` |
| `btn-inverse` | `btn-dark` |
| `alert` with no variant | `alert alert-warning` |
| `alert-error` | `alert-danger` |
| `label label-x` | `badge bg-x` |
| `badge badge-x` | `badge rounded-pill bg-x` |
| `label-important`, `badge-important` | `bg-danger` |
| `progress progress-info` > `bar` | `progress` > `progress-bar` |
| `well` | `bg-body-tertiary border rounded p-3 mb-3` |
| `pull-right`, `pull-left` | `float-end`, `float-start` |
| `text-left`, `text-right` | `text-start`, `text-end` |
| `mr-*`, `ml-*`, `pr-*`, `pl-*` | `me-*`, `ms-*`, `pe-*`, `ps-*` |
| `row` holding `col-*` from bootstrap4-utils | `row g-0` |
| `data-toggle`, `data-dismiss` | `data-bs-toggle`, `data-bs-dismiss` |
| `dropdown-menu pull-right` | `dropdown-menu dropdown-menu-end` |
| links in a dropdown | add `dropdown-item` |
| `li.divider` | `<li><hr class="dropdown-divider"></li>` |

Still to settle: modals, `input-prepend`/`input-append`/`add-on`, glyphicon `icon-*`, form layout (`control-group`, `controls`, `help-inline`), tables, `nav-pills`, accordion, grid `span*`.

## Behaviour changes

- Bootstrap 5 sets `box-sizing: border-box` on every element. An emoncms rule that sets width or height together with padding or border shrinks. Add `box-sizing: content-box` to the rule, or recalculate the size.
- Bootstrap 2 `.container-fluid` and `.row` had a clearfix that stopped child margins collapsing. `main.content-container` has `display: flow-root` for the same effect.
- Bootstrap 5 `.container-fluid` is `width: 100%`. The theme sets `width: auto` on `main`, which has a left margin for the sidebar.
- `.row > *` gets gutter padding, which overrides page padding on columns. Use `g-0` and add padding with utilities.
- Utilities are `!important`. A page rule can no longer override them.
- Links without `href` inherit their colour.
- `.dropdown-toggle` gets a caret through `::after`. Hide it where the design has none.
- `.badge:empty` is `display: none`. Pages that use empty badges as dots need `display: inline-block`.
- Bootstrap 5 has its own `.card` and `.card-header`. These clash with the emoncms card component in `Theme/css/card.css`.
- Bootstrap 5 JS registers jQuery plugins when jQuery is present, so `$(el).modal('show')` still works. Events are namespaced: `shown` becomes `shown.bs.modal`.

## Accepted differences

- Flat button colours. Bootstrap 2 used gradients.
- `bg-secondary` (#6c757d) replaces the #808080 grey used for masked services.
- Well padding 16px where Bootstrap 2 used 19px.
- Border radius on small buttons 3px where Bootstrap 2 used 4px.

## Checking a converted page

Compare each page against master with screenshots and a layout diff of every element's box and computed style. Live data (feed values, graphs, logs) changes between runs, so compare two runs of the same branch to see the noise level. Wait for PHP opcache to serve changed files before capturing.
