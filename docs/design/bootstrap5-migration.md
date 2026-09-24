# Bootstrap 5 migration

Emoncms is moving from Bootstrap 2.3.2 to Bootstrap 5.3. All modules switch in one release. There is no period where both versions are supported.

Aim for the current look first. Visual changes come later, through the theme variables.

## Files

- `Lib/bootstrap5/`: Bootstrap 5.3.8 dist (`bootstrap.min.css`, `bootstrap.bundle.min.js` with Popper).
- `Theme/css/bootstrap5-theme.css`: Bootstrap 2 metrics and colours as Bootstrap 5 variables, and component sizes (buttons, badges, alerts, modals, input groups, tables). Loaded straight after `bootstrap.min.css`.
- `Theme/css/bootstrap2-legacy.css`: parts of Bootstrap 2 kept after the switch. Element rules (reset, type, forms), glyphicon sprites, form layout (`control-group`, `controls`, `help-*`, `checkbox inline`, input sizes) with the phone rules from `bootstrap-responsive.css`, `hide`, `hidden`, `caret`, accordion, `dl-horizontal`, `input-block-level`. Attribute selectors sit in `:where()` so Bootstrap 5 classes such as `.form-control` still win. Remove sections as pages move to Bootstrap 5 components.
- `Theme/img/`: glyphicon sprites used by the legacy file.
- `Theme/css/panel.css`: the emoncms panel component, formerly `card.css`.
- `Theme/theme.php`: pages in `$bootstrap5_pages` (by `controller/action` or `controller`) load Bootstrap 5. `Theme/embed.php` does the same for the graph embed. Remove the list and Bootstrap 2 once every page is converted.
- `Theme/css/bootstrap4-utils.css`: not loaded with Bootstrap 5, which has the same utilities. `color-box` moved to `Modules/user/profile/profile.css`. Other custom classes in it (`text-tertiary`, `text-quaternary`, `text-exporting`, `m-6`, `p-6`) are not used in core.

Converted so far: `admin/info`, `input/view`, `feed/view`, `graph` and graph embed, `device/view`, all `admin` pages, `user/view`, `account` module, `schedule/view`, `dashboard` (list, editor, view), API help pages, with the process list modal and device dialog they use.

## Class conversion

| Bootstrap 2 / bootstrap4-utils | Bootstrap 5 |
|---|---|
| `btn` with no variant | `btn btn-default` |
| `btn-small` | `btn-sm` |
| `btn-mini` | `btn-xs` (theme class) |
| `btn-large` | `btn-lg` |
| `btn-inverse` | `btn-dark` |
| `alert` with no variant | `alert alert-warning` |
| `alert-error` | `alert-danger` |
| `alert-block` | remove |
| `label label-x` | `badge bg-x` |
| `label` with no variant | `badge bg-secondary` |
| `badge badge-x` | `badge rounded-pill bg-x` |
| `label-important`, `badge-important` | `bg-danger` |
| `muted` | `text-muted` |
| `text-error` | `text-danger` |
| `progress progress-info` > `bar` | `progress` > `progress-bar` |
| `well` | `bg-body-tertiary border rounded p-3 mb-3` |
| `well well-small` | `bg-body-tertiary border rounded p-2 mb-3` |
| `pull-right`, `pull-left` | `float-end`, `float-start` |
| `text-left`, `text-right` | `text-start`, `text-end` |
| `mr-*`, `ml-*`, `pr-*`, `pl-*` | `me-*`, `ms-*`, `pe-*`, `ps-*` |
| `row` holding `col-*` from bootstrap4-utils | `row g-0` |
| `row`/`row-fluid` > `spanN` | `row` > `col-N` |
| `input-prepend`, `input-append` | `input-group` |
| `add-on` | `input-group-text` |
| `table-condensed` | `table-sm` |
| `hidden-phone` | `d-none d-md-block` |
| `tr.success`, `error`, `warning`, `info` | `table-success`, `table-danger`, `table-warning`, `table-info` |
| `data-toggle`, `data-dismiss`, `data-target`, `data-parent` | `data-bs-*` |
| `dropdown-menu pull-right` | `dropdown-menu dropdown-menu-end` |
| links in a dropdown | add `dropdown-item` |
| `li.divider` | `<li><hr class="dropdown-divider"></li>` |
| `card`, `card-*` (emoncms component) | `panel`, `panel-*` |

Kept as they are: `icon-*`, `hide`, `hidden`, `caret`, `control-group`, `controls`, `help-inline`, `help-block`, `input-mini` to `input-xxlarge`, `checkbox inline`, `accordion-*`, `dl-horizontal`, `input-block-level`. The legacy file styles them.

Rename classes in CSS selectors and in JS as well as in markup: `addClass`, `removeClass`, class names held in JS data, and HTML built with string concatenation.

## Modals

Bootstrap 5 modal markup has two extra wrappers:

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

- Remove `hide` from `.modal`. The close button goes after the title.
- `$(el).modal('show')` and `'hide'` still work through the jQuery bridge. `$(el).modal()` and `$(el).modal({...})` no longer open the modal.
- Events are namespaced: `shown` becomes `shown.bs.modal`, `hidden` becomes `hidden.bs.modal`.
- In Bootstrap 2 `.modal` was the box. In Bootstrap 5 it is the full screen overlay and `.modal-dialog` is the box. Width goes in `--bs-modal-width` on the modal. Rules that set width, top or margin on `.modal` move to `.modal-dialog`, and border or radius to `.modal-content`.
- JS that reads the box position must read `.modal-content`, for example `$("#x .modal-content").offset().top` in place of `$("#x").position().top`.
- Page classes named `modal-content` or `accordion-body` clash with Bootstrap 5. `device_dialog` uses `device-modal-content`.
- The theme keeps the Bootstrap 2 look: 560px wide, 10% from the top, body capped at 400px with scrolling, grey block footer, full width with 20px margin below 768px.

## Other JS

- Collapse: `data-bs-parent` goes on the `.collapse` element, not on the toggle.
- `data-bs-toggle="button"` toggles `active` before a page click handler runs, so a handler that reads `active` sees the new state. Bootstrap 2 toggled it after. Admin log pages toggle `active` in their own handler.
- `bootstrap-datetimepicker` 0.0.11 finds its trigger through `.add-on`. Keep `add-on` next to `input-group-text` on its triggers. The legacy file shows its `collapse in` panels.
- `Lib/js/DateTimePicker.js` uses `input-group-text dtp-add-on`.
- Load page CSS and JS with `load_css` and `load_js` from `core.php`, not `<link>` or `<script>` tags with a fixed `?v=`. A fixed version serves the cached Bootstrap 2 file after a branch switch. The loaders add the file time, which `git checkout` updates.

## Behaviour changes

- Bootstrap 5 sets `box-sizing: border-box` on every element. An emoncms rule that sets width or height together with padding or border shrinks. Add `box-sizing: content-box` to the rule. Checkbox, radio, color, select and button stay border-box, as browsers size them. `boxsizing.py` cannot tell, so skip its fixes on button rules. It also misses a size and padding set in different rules, such as `.badge` padding with `.badge-get` width. Compare element sizes to find those.
- The reboot sets margins that Bootstrap 2 left to the browser. The theme restores `dl` top margin, `dd` bottom margin, `legend` float and `hr` opacity.
- Bootstrap 2 `.container-fluid` and `.row` had a clearfix that stopped child margins collapsing. `main.content-container` has `display: flow-root` for the same effect.
- Bootstrap 5 `.container-fluid` is `width: 100%`. The theme sets `width: auto` on `main`, which has a left margin for the sidebar.
- `.row > *` gets gutter padding, which overrides page padding on columns. Use `g-0` and add padding with utilities.
- `.input-group` is inline and sized to its content in the theme, as `input-prepend` was. New full width groups need `d-flex w-100`.
- Utilities are `!important`. A page rule can no longer override them. jQuery `.show()` cannot undo `d-none`, which is why `hide` stays.
- Classes that did nothing under Bootstrap 2, such as `text-muted`, some `mr-*`, `pb-md-2`, `btn-light`, `text-body` and `form-control`, now apply. Remove them where the page relied on them doing nothing, or use `btn-default` for `btn-light`.
- The theme gives `form-control` and `form-select` the Bootstrap 2 input size: 14px text, 4px 6px padding.
- Table cells inherit their colour. Bootstrap 5 sets them black by default.
- Links without `href` inherit their colour.
- `.dropdown-toggle` gets a caret through `::after`. Hide it where the design has none.
- `.badge:empty` is `display: none`. Pages that use empty badges as dots need `display: inline-block`.
- Linked labels (`a.badge[href]`) keep the darker Bootstrap 2 colours.

## Accepted differences

- Flat button colours. Bootstrap 2 used gradients.
- `bg-secondary` is the Bootstrap 2 label grey #999.
- Well padding 16px or 8px where Bootstrap 2 used 19px or 9px.
- Border radius on small buttons 3px where Bootstrap 2 used 4px.
- `btn-close` in place of the `×` character.
- Colour inputs keep the Bootstrap 2 size, which squashes the swatch to a line. A later change can let them grow.

## Status and next steps

Branch `bootstrap5` in core, device, graph, backup and account. Nothing pushed.

Done: `admin/info`, `input/view`, `feed/view`, `graph`, graph embed, `device/view`, admin pages, `user/view`, `account` module, `schedule/view`, `dashboard`, API help pages, process list modal, device dialog, card to panel rename.

Target for the remaining pages is "similar": same layout and colours, small differences allowed. Check for breakage, overlap and broken behaviour. Do not chase 2px shifts or near colours.

Remaining, core: login. Then module repos (backup, sync, network, postprocess, emailreport, setup, dashboard) and the apps in `Modules/app`. Dashboard (`Modules/dashboard`, branch `bootstrap5`) keeps a test dashboard, id 224 "bs5-test" on the test account, used by the `dashboard` state group.

Later passes: glyphicons to SVG icons, form elements to `form-control`/`form-select`, then remove sections of `bootstrap2-legacy.css` and finally Bootstrap 2 itself.

Tools are in `scripts/bootstrap5/`.

## Checking a converted page

Compare each page against master with screenshots and a layout diff of every element's box and computed style. Capture every state: modals, selections, expanded sections. Switch core and every module repo to master for the baseline. Live data (feed values, graphs, logs) and CSS transitions change between runs, so compare two runs of the same branch to see the noise level. Wait for PHP opcache to serve changed files before capturing.
