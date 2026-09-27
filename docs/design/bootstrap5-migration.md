# Converting a module from Bootstrap 2

Emoncms uses Bootstrap 5.3. Bootstrap 2 is removed, so a module that uses Bootstrap 2 markup, classes or JS needs the changes below. For the components and patterns to convert to, see `css-guide.md`.

## Removed

- Bootstrap 2 (`Lib/bootstrap/`), `Theme/css/bootstrap2-legacy.css` and `Theme/css/bootstrap4-utils.css`.
- bootstrap-datetimepicker 0.0.11. Use `Lib/js/DateTimePicker.js` (see Date picker in `css-guide.md`).
- `Theme/css/card.css`. The emoncms card component is now `panel` in `Theme/css/panel.css`.
- Theme variables `--accent`, `--bg-card`, `--text-*` and other aliases. See Variables below.

Kept: glyphicons (`icon-*`, `icon-white`, from `Theme/css/bootstrap2-icons.css`) and the `hide` class.

## Steps

1. Classes. Rename Bootstrap 2 classes in markup, CSS selectors and JS: `addClass`, `removeClass`, class names held in JS data, and HTML built with string concatenation. See Classes.
2. Forms. Every text input, select and textarea gets `form-control` or `form-select` and a width class. See Forms.
3. Modals. Add the Bootstrap 5 wrappers and update the JS. See Modals.
4. Box sizing. Bootstrap 5 sets `box-sizing: border-box` on every element. A page rule that sets a width or height together with padding or border now shrinks. Add `box-sizing: content-box` to the rule. Checkbox, radio, color, select and button stay border-box, as browsers size them. A size and padding can also be set in different rules, such as `.badge` padding with `.badge-get` width. Compare element sizes to find those.
5. Grid. `.row > *` takes full width and gutter padding. Use `row g-0` and `col-*` classes, or a flex layout, in place of hand sized columns.
6. Loading. Load page CSS and JS with `load_css()` and `load_js()`, not `<link>` or `<script>` tags with a fixed `?v=`. A fixed version serves a cached Bootstrap 2 era file after an update.
7. Variables. Replace removed theme variables. See Variables.
8. Build. Run `node scripts/bootstrap5/build.mjs --check` for classes missing from the reduced Bootstrap build. See Bootstrap build in `css-guide.md`.
9. Check each page for breakage, overlap and broken behaviour in every state: modals, selections, expanded sections.

## Classes

| Bootstrap 2 / bootstrap4-utils | Bootstrap 5 |
|---|---|
| `btn` with no variant | `btn btn-default` |
| `btn-small` | `btn-sm` |
| `btn-mini` | `btn-xs` (theme class) |
| `btn-large` | `btn-lg`, after a rebuild |
| `btn-inverse` | `btn-dark` |
| `btn-inverse btn-link` | `btn-link` |
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
| `row`/`row-fluid` > `spanN` | `row` > `col-N`, or `row g-0` and a flex layout |
| `input-prepend`, `input-append` | `input-group` |
| `add-on` | `input-group-text` |
| `table-condensed` | `table-sm` |
| `hidden-phone` | `d-none d-md-block` |
| `tr.success`, `error`, `warning`, `info` | `table-success`, `table-danger`, `table-warning`, `table-info` |
| `data-toggle`, `data-dismiss`, `data-target`, `data-parent` | `data-bs-*` |
| `dropdown-menu pull-right` | `dropdown-menu dropdown-menu-end` |
| links in a dropdown | add `dropdown-item` |
| `li.divider` | `<li><hr class="dropdown-divider"></li>` |
| `hidden` | `hide` |
| `dl-horizontal` | `dl.row g-0`, `dt.col-sm-4 text-sm-end text-truncate`, `dd.col-sm-8` |
| `caret` | `dropdown-toggle` caret, or a page rule |
| `accordion` | `collapse` with page markup, as the device dialog `tpl-*` classes |
| `card`, `card-*` (emoncms component) | `panel`, `panel-*` |

Classes that did nothing under Bootstrap 2, such as `text-muted`, some `mr-*`, `pb-md-2`, `btn-light`, `text-body` and `form-control`, now apply. Remove them where the page relied on them doing nothing, or use `btn-default` for `btn-light`.

A `btn` with only a variant loses its colour when JS removes the variant. Swap to `btn-default` in the JS.

## Forms

| Bootstrap 2 | Bootstrap 5 |
|---|---|
| bare `input`, `textarea` | `form-control input-220` |
| bare `select` | `form-select input-220` |
| `input-mini` | `input-75` |
| `input-small` | `input-105` |
| `input-medium` | `input-165` |
| `input-large` | `input-220` |
| `input-xlarge` | `input-285` |
| `input-xxlarge` | `input-545` |
| `input-block-level` | `form-control` |
| `type="color"` | `form-control form-control-color` |
| `label` above a field | `form-label` |
| `control-group` | `mb-2` |
| `controls` | remove |
| `help-block` | `form-text` |
| `help-inline` | `form-text d-inline-block align-middle ms-1 mt-0` |
| `control-group error` | `is-invalid` on the field, `text-danger` on the help text |
| `checkbox`, `radio`, `inline` labels | `d-block` or `d-inline-block me-2`, native checkbox |

- Bootstrap 2 inputs were content-box and selects border-box. A text input or textarea that keeps a pixel width from the page gets 14px more, so `width:100px` becomes `width:114px`. Select widths stay.
- A select with a Bootstrap 2 size class is 14px wider than before (`input-medium` select 150px, now `input-165`). `input-large` no longer goes full width on phones.
- A Bootstrap 5 field is a block with no bottom margin. Bootstrap 2 fields had 10px below. Stacked fields use `mb-2`.
- A field in an `.input-group` needs a width class, as Bootstrap 5 shrinks group fields to fit.
- `form-select` draws its own box, so its text follows line height and padding. A page rule that sets a select's height must set line height to the height less padding and border.
- Checkboxes and radios stay native. `form-check-input` would restyle them.

## Modals

Bootstrap 5 modal markup has two extra wrappers, `modal-dialog` and `modal-content`. See Modals in `css-guide.md` for the markup.

- Remove `hide` from `.modal`. Use `btn-close` after the title in place of the `×` character.
- `$(el).modal('show')` and `'hide'` work through the jQuery bridge. `$(el).modal()` and `$(el).modal({...})` no longer open the modal.
- Events are namespaced: `shown` becomes `shown.bs.modal`, `hidden` becomes `hidden.bs.modal`.
- In Bootstrap 2 `.modal` was the box. In Bootstrap 5 it is the full screen overlay and `.modal-dialog` is the box. Rules that set width, top or margin on `.modal` move to `.modal-dialog`, and border or radius to `.modal-content`. Width goes in `--bs-modal-width` on the modal.
- JS that reads the box position must read `.modal-content`, for example `$("#x .modal-content").offset().top` in place of `$("#x").position().top`.
- Rename page classes that clash with Bootstrap 5, such as `modal-content` or `accordion-body`.

## Other JS

- Collapse: `data-bs-parent` goes on the `.collapse` element, not on the toggle. `.collapse('hide')` only hides an element that has `collapse show`. Bootstrap 2 hid any element.
- `data-bs-toggle="button"` toggles `active` before a page click handler runs. Bootstrap 2 toggled it after. A handler that reads `active` sees the new state.
- jQuery `.show()` cannot undo Bootstrap 5 `d-none`. Use `hide` for elements shown from JS.

## Variables

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

Also removed without a replacement: `--accent-hover`, `--accent-bg-hover`, `--accent-border`, `--accent-row-hover`, `--focus-ring`, `--controls-bg`, `--border-card`, `--color-cat-default`, `--spacer`, `--font-heading`, `--font-base` and `--s1` to `--s6`.

## Differences from Bootstrap 2

The theme keeps most of the Bootstrap 2 look: element styles, input and button sizes, modal size, label and alert colours. These differ:

- Flat button colours. Bootstrap 2 used gradients.
- `btn-link` underlines by default. Bootstrap 2 underlined on hover only.
- `.btn-group` is flex, so its buttons stretch, shrink and wrap their text. Use a two class selector to stop stretching, and `text-nowrap`.
- `.dropdown-toggle` draws a caret with `::after`. Hide it where the design has none.
- `.badge:empty` is hidden. Badges used as dots need `display: inline-block`.
- `.input-group` is inline and sized to its content, as `input-prepend` was. New full width groups need `d-flex w-100`.
- Utilities are `!important`. A page rule cannot override them.
- `.lead` is 20px, weight 300, line height 20px. Bootstrap 2 used 21px, weight 200, line height 30px.
- `bg-secondary` is the Bootstrap 2 label grey #999.
- Well padding 16px or 8px where Bootstrap 2 used 19px or 9px.
- Border radius on small buttons 3px where Bootstrap 2 used 4px.
