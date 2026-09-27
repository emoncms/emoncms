# Bootstrap 5 release

Emoncms moves from Bootstrap 2.3.2 to Bootstrap 5.3.8. Core and every module maintained by OpenEnergyMonitor change together in this release. Bootstrap 2 is removed, so third party modules that use Bootstrap 2 markup, classes or JS need changes.

- Converting a module: `docs/design/bootstrap5-migration.md`.
- Styling pages and modules: `docs/design/css-guide.md`.

## Summary

Bootstrap 2 dates from 2013. Emoncms carried a copy of Bootstrap 4 utilities on top of it, and each page had its own CSS for similar components. This release replaces Bootstrap 2 with Bootstrap 5 and sets out one set of shared patterns for the whole application.

The work had four aims:

- Move every page to Bootstrap 5, keeping the emoncms look.
- Reduce the amount of CSS significantly.
- Use modern CSS: variables, colour modes, grid and flex.
- Give future development clear, documented patterns.

## What changed

**Framework.** Bootstrap 5.3.8 on every page, with a reduced build that leaves out unused components (98 KB, the stock file is 232 KB). The old date picker is replaced by one date picker for Vue and jQuery pages.

**Theme.** One set of colour and size variables in `Theme/css/bootstrap5-theme.css`, with a light and a dark set on the Bootstrap 5.3 colour mode (`data-bs-theme`). The theme keeps the emoncms look: element styles, compact fields and buttons, modal size and palette.

**Shared components.** Pages now use shared components in place of their own copies: page header, panel with key and value rows and forms, group list with a sticky toolbar, pastel tags, reference page components for the API and Network pages, and an app kit for the apps. All are documented in the CSS guide.

**Pages.** Redesigned on the shared components:

- My Account, Post Process, Schedule and Email Reports on panels.
- Sync with Upload and Download tabs. Sync Inputs and Sync Dashboards are removed.
- Network and the setup wizard on the dark reference look.
- Admin pages. The Components page groups components by install folder, shows the remote protocol, and has Update all. Update Emoncms Only is removed: use Full Update or Components.
- API help pages on the dark reference look.
- Every app on the app kit, dark or light. The app config panel is rebuilt.

Other pages keep their layout, converted to Bootstrap 5 classes and the theme variables.

**Removed.** Bootstrap 2, `bootstrap2-legacy.css`, `bootstrap4-utils.css`, `card.css` (now `panel.css`), the old date picker, the app `dark.css`, `light.css` and `graph.css`, and the old theme variable aliases.

**Not yet changed.** Glyphicons stay, from `bootstrap2-icons.css`. Moving them to the SVG icon set is planned.

## CSS size

All CSS in core and the module repos, master against this release (27 September 2026). Counts cover every `.css` file and every `<style>` block in views, whether a page loads it or not. Raw bytes, not gzipped.

| | Master | This release | Change |
|---|---|---|---|
| Bytes | 570,129 | 369,255 | −35% |
| Rules | 4,291 | 2,623 | −39% |
| Declarations | 11,021 | 6,514 | −41% |

| Bytes, rules | Master | This release | Change |
|---|---|---|---|
| Third party (Bootstrap, date picker, font) | 279,644, 2,466 | 98,744, 1,045 | −65%, −58% |
| Emoncms CSS files and `<style>` blocks | 195,287, 1,741 | 165,329, 1,351 | −15%, −22% |
| Icons (`svg-icons.css`, `bootstrap2-icons.css`) | 95,196, 84 | 105,180, 227 | +10% |

In emoncms CSS, hex colours fell from 711 to 373 and `!important` from 234 to 37. `<style>` blocks fell from 34 views and 62,592 bytes to 8 views and 9,944 bytes. Inline `style=""` attributes fell from 1,136 to 551.

Icons grew as glyphicons moved out of Bootstrap 2 into `bootstrap2-icons.css`. Replacing glyphicons with SVG icons is a later step.

## For module authors

A module that uses Bootstrap 2 needs converting before it works with this release. The main changes are class renames, `form-control` on every field, modal markup and JS, and box sizing. `docs/design/bootstrap5-migration.md` has the full list and the steps.

The Bootstrap build includes only the classes used in core and the emoncms modules. Modules outside the emoncms repos are not scanned. If a class they use is missing, ask for it to be added to the build.
