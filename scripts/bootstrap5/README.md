# Bootstrap 5 migration tools

Scripts used to convert pages from Bootstrap 2 to Bootstrap 5 and to check them against master. See `docs/design/bootstrap5-migration.md` for the conversion rules.

Setup: `npm install` in this folder (Playwright). The browser defaults to `/usr/bin/google-chrome`, set `CHROME` to change it. The site defaults to `http://localhost/emoncms`, set `EMONCMS_URL` to change it. Logins come from `EMONCMS_USER` and `EMONCMS_PASS`. Do not commit credentials.

## Converters

Each prints its changes. Review the diff after running.

- `cvt_modal.py files...`: Bootstrap 2 modal markup to Bootstrap 5 (wrappers, close button, `data-bs-*`).
- `cvt_classes.py files...`: class renames in `class` attributes, Vue `:class` literals and jQuery class calls. Skips class strings built by concatenation and reports them for hand editing.
- `cvt_css.py files...`: class renames in CSS selectors, in `.css` files and `<style>` blocks.
- `boxsizing.py [--fix] files...`: rules that set a size together with padding or border. `--fix` adds `box-sizing: content-box`.

## Checks

- `states.mjs <outdir> <group> [width]`: drive page states (modals, selections) and save a screenshot and a layout dump for each. Groups are defined at the top of the file. Steps are `click` (pointer click), `dispatch` (click event only, for buttons under an overlay), `select` (option by `index`) and `wait` (ms).
- `onmaster.sh <command...>`: run a command with core and every module repo on master, local changes stashed, then restore. Use it to capture the baseline.
- `summ.sh <masterdir> <branchdir> <width> states...`: count elements that moved more than 1px.
- `boxcmp.mjs a.json b.json [limit]`: group style differences by property change.
- `bbox.py a.json b.json`: elements resized by the border-box change.
- `cmp.mjs dirA dirB`: pixel difference per screenshot.
- `crop.mjs out.png x y w h files...`: crop screenshots side by side.

## Typical run

```sh
export EMONCMS_USER=... EMONCMS_PASS=...
sh onmaster.sh node states.mjs m-input input 1280
node states.mjs b-input input 1280
sh summ.sh m-input b-input 1280 input_list input_edit
node boxcmp.mjs m-input/input_list@1280.json b-input/input_list@1280.json
```

Modal wrappers change DOM paths, so elements inside converted modals show large false moves in `summ.sh`. Check modals from the screenshots.
