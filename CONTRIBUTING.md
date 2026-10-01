# Contributing

## State of development

**September 2026**: Emoncms 12 completes a round of consolidation. Over the years Emoncms gained many ways of doing the same thing, in features and in code, and that made it hard to maintain. The focus is on the core use case, with one consistent implementation.

Done in this round:

- **Bootstrap 5.** Bootstrap 2 and the Bootstrap 4 utility classes are replaced by a reduced Bootstrap 5.3 build and one theme, with shared page patterns and much less custom CSS. See the [CSS guide](docs/developer/design/css-guide.md) and the [release notes](docs/developer/notes/bootstrap5-release.md).
- **Vis module retired.** Its charts are part of the dashboard module, or replaced by the graph module.
- **Dashboard module 4.** Dashboards are stored as a JSON document. Text and HTML are checked against an allowlist in place of the AntiXSS library. Existing dashboards, including multigraphs and other retired charts, are converted when they load.
- **Apps.** My Electric, My Solar and My Solar Battery are combined in My Electric Flow. Similar or unmaintained apps are archived. Apps share one style kit, in light and dark.
- **Lists.** Inputs, feeds and devices share one CSS grid and Vue implementation.
- **Libraries.** Vue 3, jQuery 4 and Flot 5.1.
- **Documentation.** The user guide is rewritten, with screenshots captured by script. There are developer docs for the architecture, modules and development setup, and a guide for AI coding agents. See [docs/README.md](docs/README.md).

## Where help is welcome

- **Responsive dashboards.** A second layout mode, chosen per dashboard, for good looking dashboards on phones as well as desktops. Widgets sit in rows of cells in place of fixed coordinates. Rows stack on narrow screens. A card style draws each cell as a panel, a flat style has no panels. The layout is stored in the dashboard document, and switching back restores the fixed positions. The groundwork is done: stable widget ids, the widget interface and the editor holding the document. See `notes/RESPONSIVE.md` in the [dashboard module](https://github.com/emoncms/dashboard).
- **Modular apps.** Splitting the app module so that each app is its own module, or can be installed on its own. This would make it much easier for users to write and maintain their own app dashboards, and to share them.
- **Code outside the web root.** Restructuring the install so that only `index.php` and static assets are served, with the PHP code in `Modules/`, `Lib/` and `scripts/` outside the public folder. The web server could then not serve PHP files directly, and the direct access guard in each file would no longer be needed.
- **Updates and firmware out of core.** Moving the updater, component updates, firmware upload and serial config (`Modules/admin/update`, `Modules/admin/serial`, `Modules/admin/components`) into their own module. A cloud install would then carry no Raspberry Pi features, and a Docker install, which updates by pulling a new image, would carry no update machinery.
- **Feed migration.** Moving feeds stored as PHPTimeSeries and MysqlTimeSeries to PHPFina (fixed interval). Scripts in [usefulscripts](https://github.com/emoncms/usefulscripts) convert PHPTimeSeries from the command line. There is nothing in the web interface.
- **Icons.** Replacing the Glyphicons (`icon-*`) with SVG icons, then removing `bootstrap2-icons.css`. See section 12 of the [CSS guide](docs/developer/design/css-guide.md).
- **Light and dark mode.** A site wide setting. The remaining fixed colours in page CSS need moving to theme variables first.
- **Tests.** More coverage of the API and models. See [tests/readme.md](tests/readme.md).
- **Translations.** Removing the remaining gettext calls, since translations now use JSON files.

If one of these interests you, please get in touch before starting. We are happy to talk ideas through.

## How to contribute

1. Discuss the change in an issue, or by email.
2. Set up a development install. See [docs/developer/development.md](docs/developer/development.md).
3. Make the change on `master`, in the repository that owns the code. Core and each module are separate repositories.
4. Run the checks: `php composer.phar run test` and the unit tests.
5. Update the user guide in `docs` if the change affects what users see.
6. Open a pull request against `master`, and say how you tested it.

Keep pull requests small, with one change each.

## Using AI agents

Issues and pull requests written with the help of an AI agent are welcome. Please say that an agent was used, check the result yourself before submitting, and keep pull requests small. Agents should read [AGENTS.md](AGENTS.md) for the project layout, checks and conventions.

## Security

Report security problems privately, not in an issue. See [SECURITY.md](SECURITY.md).

## Contact

- hello@openenergymonitor.zendesk.com (Trystan, Glyn and Gwil)
- trystanlea@openenergymonitor.org
- [Community forum](https://community.openenergymonitor.org)
