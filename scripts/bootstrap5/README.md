# Bootstrap 5 tools

Scripts to build the reduced Bootstrap stylesheet. See `docs/design/bootstrap5-migration.md` for the conversion rules.

Setup: `npm install` in this folder (Bootstrap source, Sass, PostCSS, PurgeCSS).

## Bootstrap build

`Lib/bootstrap5/css/bootstrap.min.css` is a reduced build of Bootstrap 5.3.8. It is not the stock file.

- `build.mjs [entry.scss] [--out file] [--no-purge]`: compile `scss/bootstrap.scss` with Bootstrap's own pipeline (Sass 1.78.0, autoprefixer, clean-css), then run `purge.mjs` and `bsmissing.mjs`. Writes `Lib/bootstrap5/css/bootstrap.min.css` by default. A build from Bootstrap's full import list matches the stock file rule for rule.
- `scss/bootstrap.scss`: Bootstrap import list with unused components commented out. To use one of them, uncomment it and rebuild.
- `scan.mjs`: source files scanned for class names, shared by `purge.mjs` and `bsmissing.mjs`. Every `.php`, `.js`, `.html` and `.json` file in core and the modules (symlinks followed), with `<style>` blocks removed. The safelist covers classes that Bootstrap JS adds and names built at runtime from a prefix.
- `purge.mjs [in.css] [--out file] [--report dir]`: remove selectors whose class names do not appear in the scanned source. Whole families are kept whether used or not: the grid at every breakpoint (`row`, `col-*`, `offset-*`, `g-*`, `row-cols-*`) and the utility families without breakpoint variants (`d-*`, `flex-*`, spacing, `w-*`, `h-*`, `text-*`, `bg-*`, `border-*` and similar). Responsive utility variants such as `d-md-flex` are kept only when used. Without `--out` it reports sizes and rejected selectors only.
- `bsmissing.mjs [built.css]`: Bootstrap classes that appear in the source but are missing from the build. A hit needs a rebuild, or its component uncommented in `scss/bootstrap.scss`. Words that collide with class names are listed at the top of the script.

Common classes work without a rebuild. A responsive utility variant or an omitted component needs one:

```sh
node build.mjs
node bsmissing.mjs
```
