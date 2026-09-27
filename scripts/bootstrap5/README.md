# Bootstrap build

Builds `Lib/bootstrap5/css/bootstrap.min.css`, a reduced build of Bootstrap 5.3.8. See Bootstrap build in `docs/design/css-guide.md`.

Setup: `npm ci` in this folder. It installs the versions in `package-lock.json` (Bootstrap source, Sass, PostCSS, clean-css, PurgeCSS).

- `node build.mjs`: compile `scss/bootstrap.scss` with Bootstrap's own pipeline (Sass 1.78.0, autoprefixer, clean-css), remove selectors whose classes do not appear in the source of core and the modules, write the file, then list Bootstrap classes the source uses that the build lacks.
- `node build.mjs --check`: list missing classes only. Exit code 1 when there are any.
- `scss/bootstrap.scss`: Bootstrap import list with unused components commented out. To use one of them, uncomment it and rebuild.

The source scan covers every `.php`, `.js`, `.html` and `.json` file in core and the modules (symlinks followed), with `<style>` blocks removed. Kept whether used or not: the grid at every breakpoint and the utility families without breakpoint variants (`d-*`, `flex-*`, spacing, `w-*`, `h-*`, `text-*`, `bg-*`, `border-*` and similar), plus classes that Bootstrap JS adds and names built at runtime from a prefix. These lists and the words that collide with class names are in `build.mjs`.
