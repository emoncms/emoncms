# Bootstrap 5 migration tools

Scripts used to convert pages from Bootstrap 2 to Bootstrap 5 and to check them against master. See `docs/design/bootstrap5-migration.md` for the conversion rules.

Setup: `npm install` in this folder (Playwright, Bootstrap source, Sass, PurgeCSS). The browser defaults to `/usr/bin/google-chrome`, set `CHROME` to change it. The site defaults to `http://localhost/emoncms`, set `EMONCMS_URL` to change it. Logins come from `EMONCMS_USER` and `EMONCMS_PASS`. Do not commit credentials.

## Converters

Each prints its changes. Review the diff after running.

- `cvt_modal.py files...`: Bootstrap 2 modal markup to Bootstrap 5 (wrappers, close button, `data-bs-*`).
- `cvt_classes.py files...`: class renames in `class` attributes, Vue `:class` literals and jQuery class calls. Skips class strings built by concatenation and reports them for hand editing.
- `cvt_css.py files...`: class renames in CSS selectors, in `.css` files and `<style>` blocks.
- `boxsizing.py [--fix] files...`: rules that set a size together with padding or border. `--fix` adds `box-sizing: content-box`.

## Bootstrap build

`Lib/bootstrap5/css/bootstrap.min.css` is a reduced build of Bootstrap 5.3.8. It is not the stock file.

- `build.mjs [entry.scss] [--out file] [--no-purge]`: compile `scss/bootstrap.scss` with Bootstrap's own pipeline (Sass 1.78.0, autoprefixer, clean-css), then run `purge.mjs` and `bsmissing.mjs`. Writes `Lib/bootstrap5/css/bootstrap.min.css` by default. A build from Bootstrap's full import list matches the stock file rule for rule.
- `scss/bootstrap.scss`: Bootstrap import list with unused components commented out. To use one of them, uncomment it and rebuild.
- `scan.mjs`: source files scanned for class names, shared by `purge.mjs` and `deadcss.mjs`. Every `.php`, `.js`, `.html` and `.json` file in core and the modules (symlinks followed), with `<style>` blocks removed. The safelist covers classes that Bootstrap JS adds and names built at runtime from a prefix.
- `purge.mjs [in.css] [--out file] [--report dir]`: remove selectors whose class names do not appear in the scanned source. No browser needed. Whole families are kept whether used or not: the grid at every breakpoint (`row`, `col-*`, `offset-*`, `g-*`, `row-cols-*`) and the utility families without breakpoint variants (`d-*`, `flex-*`, spacing, `w-*`, `h-*`, `text-*`, `bg-*`, `border-*` and similar). Responsive utility variants such as `d-md-flex` are kept only when used. Without `--out` it reports sizes and rejected selectors only.
- `bsmissing.mjs [built.css]`: Bootstrap classes that appear in the source but are missing from the build. A hit needs a rebuild, or its component uncommented in `scss/bootstrap.scss`. Words that collide with class names are listed at the top of the script.
- `abcss.mjs <b.css> <outdir> <user|-> <group...>`: check a new build. Each page state is captured with the served stylesheet, then with the Bootstrap link swapped to `b.css`, then swapped back. Reports computed style and pixel differences per state and ignores elements that change between the two A captures. Custom properties that `b.css` no longer defines are listed apart. Password is `EMONCMS_PASS` or the user name.

Common classes work without a rebuild. A responsive utility variant or an omitted component needs one. `bsmissing.mjs` lists such classes. Build, then check:

```sh
node build.mjs --out /tmp/b.css
node abcss.mjs /tmp/b.css ab test input feed device
cp /tmp/b.css ../../Lib/bootstrap5/css/bootstrap.min.css
```

## Checks

- `formlint.py dirs...`: form fields without `form-control` or `form-select`, including fields built in JS strings, and leftover Bootstrap 2 form classes.
- `groups.mjs`: page states per group, shared by `states.mjs` and `abcss.mjs`.
- `states.mjs <outdir> <group> [width]`: drive page states (modals, selections) and save a screenshot and a layout dump for each, plus `css-coverage@<width>.json`, the byte ranges of each stylesheet matched during the run. Groups are defined at the top of the file. Steps are `click` (pointer click), `dispatch` (click event only, for buttons under an overlay), `select` (option by `index`), `fill` (text `value`), `clickat` (mouse events at `x`, `y` inside an element, sent to the element) and `wait` (ms).
- `onmaster.sh <command...>`: run a command with core and every module repo on master, local changes stashed, then restore. Use it to capture the baseline.
- `summ.sh <masterdir> <branchdir> <width> states...`: count elements that moved more than 1px.
- `boxcmp.mjs a.json b.json [limit]`: group style differences by property change.
- `bbox.py a.json b.json`: elements resized by the border-box change.
- `cmp.mjs dirA dirB`: pixel difference per screenshot.
- `crop.mjs out.png x y w h files...`: crop screenshots side by side.
- `cssbytes.sh`: raw and gzipped bytes of the hand written CSS, the `<style>` blocks and `bootstrap.min.css`.
- `csscount.py [-v]`: rules, selectors and declarations in the same three sets. `-v` lists each file.
- `colcount.py`: hex literals and `!important` in the same sets, for the working tree and `HEAD`.
- `deadcss.mjs [-v]`: rules in the hand written CSS and `<style>` blocks whose classes or ids do not appear in the scanned source, per file, plus custom properties that are never read. Report only, for review by hand. Known runtime names are listed at the top of the script. `-v` lists each selector with its line.
- `csscov.mjs dirs...`: used share of each stylesheet from the coverage files in the given output folders, merged, with Bootstrap and hand written totals. Run every group first so the union covers the page list.

## Quick checks

- `appshot.mjs <outdir> <user> "name=page|click|click" ...`: logs in (password equals the user name), captures each state full page and prints page errors and emoncms error dialogs. `W=400` for phone width, `WAIT=ms` before clicks. Emoncms shows JS errors in its own alert, so the dialog log matters.
- `netshot.mjs <outdir>`: Network page and setup wizard as admin, with WiFi networks injected through `window.app` and one row open.
- `tags.py file.php ...`: tag balance of a view, PHP and scripts stripped.

## Typical run

```sh
export EMONCMS_USER=... EMONCMS_PASS=...
sh onmaster.sh node states.mjs m-input input 1280
node states.mjs b-input input 1280
sh summ.sh m-input b-input 1280 input_list input_edit
node boxcmp.mjs m-input/input_list@1280.json b-input/input_list@1280.json
```

Modal wrappers change DOM paths, so elements inside converted modals show large false moves in `summ.sh`. Check modals from the screenshots.
