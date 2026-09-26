# Bootstrap 5 migration

Emoncms is moving from Bootstrap 2.3.2 to Bootstrap 5.3. All modules switch in one release. There is no period where both versions are supported.

Aim for the current look first. Visual changes come later, through the theme variables.

## Files

- `Lib/bootstrap5/`: Bootstrap 5.3.8 dist (`bootstrap.min.css`, `bootstrap.bundle.min.js` with Popper).
- `Theme/css/bootstrap5-theme.css`: Bootstrap 2 metrics and colours as Bootstrap 5 variables, the element look where the Bootstrap 5 reboot differs (links, headings, paragraphs, lists, `hr`, `code`, `pre`), component sizes (buttons, badges, alerts, modals, input groups, tables, form controls) and `hide`. Loaded straight after `bootstrap.min.css`.
- `Theme/css/bootstrap2-legacy.css` is removed. Its rules moved to Bootstrap 5 components or the theme.
- `Theme/css/bootstrap2-icons.css`: Bootstrap 2 glyphicon sprites (`icon-*`, `icon-white`). Loaded after the theme.
- `Theme/img/`: glyphicon sprites used by `bootstrap2-icons.css`.
- `Theme/css/panel.css`: the emoncms panel component, formerly `card.css`.
- `Theme/theme.php` and `Theme/embed.php` load Bootstrap 5 on every page. Bootstrap 2 (`Lib/bootstrap/`) and `Theme/css/bootstrap4-utils.css` are removed.
- Custom classes from `bootstrap4-utils.css`: `color-box` moved to `Modules/user/profile/profile.css`. Classes the apps use are in `Modules/app/Views/css/utils.css`.

Converted so far: `admin/info`, `input/view`, `feed/view`, `graph` and graph embed, `device/view`, all `admin` pages, `user` (login and account page), `account` module, `schedule/view`, `dashboard` (list, editor, view), API help pages, apps (`Modules/app`), with the process list modal and device dialog they use.

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

Kept as they are: `icon-*` (styled by `bootstrap2-icons.css`) and `hide`.

Removed with the legacy file:

| Bootstrap 2 | Now |
|---|---|
| `hidden` | `hide` |
| `dl-horizontal` | `dl.row g-0`, `dt.col-sm-4 text-sm-end text-truncate`, `dd.col-sm-8` |
| `caret` | `dropdown-toggle` caret, or a page rule (graph `tag-caret`) |
| accordion in the device dialog | `tpl-list`, `tpl-group`, `tpl-heading`, `tpl-toggle`, `tpl-inner` in `device_dialog.css` |

## Forms

Every text input, select and textarea has `form-control` or `form-select`. Bare fields are unstyled, so a field without them shows as a plain browser field. `scripts/bootstrap5/formlint.py` lists fields without them and leftover Bootstrap 2 form classes.

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

- Width classes are theme classes. They set the total width (padding and border included), `display: inline-block` and `vertical-align: middle`, and only apply together with `form-control` or `form-select`. `input-auto` sizes a select to its options.
- `input-285` and `input-545` go full width below 768px, except in an input group.
- A Bootstrap 5 field is a full width block with no bottom margin. Bootstrap 2 fields had 10px below. Stacked fields use `mb-2`.
- A field in an `.input-group` needs a width class, as Bootstrap 5 shrinks group fields to fit.
- `form-label` is block with 5px below, as the Bootstrap 2 label. `form-text` is #595959.
- Readonly fields keep the grey background.
- Bootstrap 2 inputs were content-box and selects border-box. A text input or textarea that keeps a pixel width from the page gets 14px more, so `width:100px` becomes `width:114px`. Select widths stay.
- Accepted: a select with a Bootstrap 2 size class is 14px wider than before (`input-medium` select 150px, now `input-165`). `input-large` no longer goes full width on phones.
- `form-select` draws its own box, so its text follows line height and padding. A native select centred its text. A page rule that sets a select's height must set line height to the height less padding and border, or the text sits low.
- Checkboxes and radios stay native. `form-check-input` would restyle them.

Rename classes in CSS selectors and in JS as well as in markup: `addClass`, `removeClass`, class names held in JS data, and HTML built with string concatenation.

## Date picker

`Lib/js/DateTimePicker.js` with `Theme/css/datetimepicker.css` is the one date picker. bootstrap-datetimepicker 0.0.11 is removed.

- Vue template: `<date-time-picker v-model="start" @change="reload">` inside an `.input-group`. It renders an input, a calendar button and a Bootstrap dropdown menu as children of the group.
- Other pages: `DateTimePicker.attach(input, { value, onChange, buttonClass })` adds the button and menu after an existing input in an `.input-group`. The input keeps its id, value and events, and gets a `change` event when a date is applied. Returns `getDate()` and `setDate(date)`; `setDate` does not call `onChange`.
- Values are local time, `YYYY-MM-DD HH:MM:SS`. `DateTimePicker.parse` and `DateTimePicker.format` convert to and from `Date`.
- The menu uses Popper with fixed positioning, so a scrolling modal body does not clip it. Bootstrap closes it on an outside click or Esc.
- The Dropdown is created before Bootstrap's own click handler, which runs in the capture phase and would create one with default options.
- Colours come from `--bs-*` variables.
- A page rule such as `.x input[type=text]` also reaches the time inputs in the menu. Use a child selector for the page's own input.

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

- Collapse: `data-bs-parent` goes on the `.collapse` element, not on the toggle. `.collapse('hide')` only hides an element that has `collapse show`; Bootstrap 2 hid any element.
- `data-bs-toggle="button"` toggles `active` before a page click handler runs, so a handler that reads `active` sees the new state. Bootstrap 2 toggled it after. Admin log pages toggle `active` in their own handler.
- Load page CSS and JS with `load_css` and `load_js` from `core.php`, not `<link>` or `<script>` tags with a fixed `?v=`. A fixed version serves the cached Bootstrap 2 file after a branch switch. The loaders add the file time, which `git checkout` updates.

## Palette and tokens

All colours and shared values are at the top of `bootstrap5-theme.css`, in three blocks. Components in the theme read them, so a look change is an edit there.

- `:root, [data-bs-theme]`: values shared by both modes (button colours, type, shape, spacing) and aliases such as `--accent` and `--bs-link-color`. The selector covers every theme root, so aliases take the colours of that root's mode.
- `:root, [data-bs-theme="light"]`: the light set.
- `[data-bs-theme="dark"]`: the dark set, from the API pages and MyElectricFlow. Primary `#44b3e2`, surfaces `#2e2e2e`, text `#ccc`, borders `#3f3f3f`.

The light and dark sets declare the same variables, so the dark set also works on `<html>`. A new colour goes in both sets. `[data-bs-theme]` also sets the text colour, as Bootstrap sets it on `body` only.

- Primary is the emoncms accent `#2a8fc7` in light and `#44b3e2` in dark (`--bs-primary`, `--bs-primary-rgb`), with `--ec-primary-dark` for hover and borders and `--ec-primary-darker` for active. Link hover is darker in light and lighter in dark. Links, primary buttons, focus rings and the active item of dropdowns, pills, pagination and list groups use it.
- Coloured buttons keep the Bootstrap 2 colours in `--ec-btn-<colour>`, `-dark` and `-darker`. Text and label colours are `--bs-<colour>-rgb`. Alerts and row tints use `--bs-<colour>-text-emphasis`, `-bg-subtle` and `-border-subtle`.
- Greys: `--bs-body-color`, `--bs-secondary-color` (muted), `--bs-border-color`, `--ec-input-border-color`, `--bs-secondary-bg` and `--bs-tertiary-bg`, each with its `-rgb` where Bootstrap reads one.
- Component colours: `--ec-btn-default-*`, `--ec-highlight` and `--ec-highlight-soft` (inset line and text shadow, transparent in dark), `--bs-code-color`, `--ec-code-bg`, `--ec-code-border`, `--ec-table-striped-bg`, `--ec-divider`, `--ec-form-text`, and for the group list `--ec-list-row-bg`, `--ec-list-selected-bg`, `--ec-list-selected-hover-bg`, `--ec-list-status`.
- The emoncms tokens (`--accent`, `--border`, `--bg-card`, `--text-*`, `--font-*`, `--s1` to `--s6`) are defined here too, as aliases of the Bootstrap variables where the role is the same. `emoncms-base.css` keeps the colour scheme classes (`.theme-*`), which set the top menu colours, and the sidebar sets (`.sidebar-dark`, `.sidebar-light`). Sticky list toolbars use `--bg-menu-top-active`, so they follow the scheme.
- Page CSS still has fixed colours, including 72 uses of the menu blue `#44b3e2`. Move them to variables page by page.

## Element look

The Bootstrap 5 reboot differs from Bootstrap 2 on headings (weight 500, no top margin, 1.2 line height), links (always underlined), paragraph spacing (16px) and lists. Emoncms pages rely on the Bootstrap 2 values, so the theme sets them in its base section: bold headings with 10px margins and the Bootstrap 2 sizes, links underlined on hover, 10px paragraph margin, list indent, `hr`, `code` and `pre`. Change the look there, not with utilities on each page.

The link hover rule uses `a:where(:hover, :focus)` so `.btn`, `.nav-link` and `.dropdown-item` keep no underline.

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
- `btn-link` underlines by default. Bootstrap 2 underlined on hover only.
- `btn-inverse btn-link` was a plain link in Bootstrap 2, as `btn-link` came later. `btn-dark btn-link` shows a dark button. Drop `btn-dark`.
- `.btn-group > .btn` is `flex: 1 1 auto`, which beats a page rule on one class. Use a two class selector to stop buttons stretching.
- `.btn-group` is flex, so its buttons shrink and wrap their text in a narrow cell. Add `text-nowrap`.
- A `btn` with only a variant loses its colour when JS removes the variant. Swap to `btn-default` in the JS.
- `cvt_classes.py` does not convert `row`/`row-fluid` with `spanN`. Convert those by hand.
- `.lead` is 20px, weight 300, line height 20px. Bootstrap 2 used 21px, weight 200, line height 30px.
- Lists indent by margin as in Bootstrap 2 (25px, no padding). Page rules that reset the margin rely on this. `.nav` has no margin.

## Accepted differences

- Flat button colours. Bootstrap 2 used gradients.
- `bg-secondary` is the Bootstrap 2 label grey #999.
- Well padding 16px or 8px where Bootstrap 2 used 19px or 9px.
- Border radius on small buttons 3px where Bootstrap 2 used 4px.
- `btn-close` in place of the `×` character.

## Status and next steps

Bootstrap 2 is removed. Every page loads Bootstrap 5.

Branch `bootstrap5` in core and the device, graph, account, dashboard, backup, sync, network, postprocess, emailreport, config and app modules. Postprocess branches from `stable`. Nothing pushed.

Done: `admin/info`, `input/view`, `feed/view`, `graph`, graph embed, `device/view`, admin pages, `user` (login and account page), `account` module, `schedule/view`, `dashboard`, API help pages, apps, process list modal, device dialog, card to panel rename.

Target for the remaining pages is "similar": same layout and colours, small differences allowed. Check for breakage, overlap and broken behaviour. Do not chase 2px shifts or near colours.

Core pages are done. Modules done: backup, sync, network (and setup, which uses the network view), postprocess, emailreport, config, app.

Apps (`Modules/app`, branch `bootstrap5`): the apps used `bootstrap4-utils.css` classes, which Bootstrap 5 lacks or colours differently (`text-light` #aaa, `text-primary`, `text-tertiary`, `text-quaternary`, `d-xs-*`, wrapping `justify-content-between`). `Modules/app/Views/css/utils.css` keeps them. It is loaded by the config panel (`Lib/appconf/appconf.php`), which every app includes. App stylesheets load with `load_css`. The test account has instances of myelectricflow, myheatpump, timeofuse2, myelectric2, myboiler, ukgrid and co2monitor (`app` state group), and the admin account has solarbatterysim (`app_admin`). The other 14 apps were converted from the markup but not rendered. They need test instances before they can be checked. Dashboard (`Modules/dashboard`, branch `bootstrap5`) keeps a test dashboard, id 224 "bs5-test" on the test account, used by the `dashboard` state group.

Form elements are on `form-control`/`form-select`.

Later passes: look changes through the palette, page CSS colours to variables, dark mode (`data-bs-theme`), colour schemes setting `--bs-primary`, glyphicons to SVG icons.

Tools are in `scripts/bootstrap5/`.

## Checking a converted page

Compare each page against master with screenshots and a layout diff of every element's box and computed style. Capture every state: modals, selections, expanded sections. Switch core and every module repo to master for the baseline. `onmaster.sh` finds each module's repo through git, as symlinked modules point into a subfolder of their repo. Live data (feed values, graphs, logs) and CSS transitions change between runs, so compare two runs of the same branch to see the noise level. Wait for PHP opcache to serve changed files before capturing.
