# Guide for AI coding agents

This file is for AI agents working on the Emoncms code base, and for people who direct them. Issues and pull requests made with the help of an agent are welcome.

## Project

Emoncms is a PHP web application for logging and visualising energy, temperature and other environmental data. It runs on the emonPi and emonBase (Raspberry Pi) and on servers, including emoncms.org.

Read first:

- [docs/developer/development.md](docs/developer/development.md): local install, test data, tests, debugging.
- [docs/developer/design/architecture.md](docs/developer/design/architecture.md): request flow, directory layout, services.
- [docs/developer/design/developing-a-new-module.md](docs/developer/design/developing-a-new-module.md): controllers, models, views, menus, schema, translations.
- [docs/developer/design/css-guide.md](docs/developer/design/css-guide.md): page structure and CSS for the Bootstrap 5 UI.
- [docs/README.md](docs/README.md): docs contents, user guide and developer docs.

The HTTP API reference is generated from the module definitions. A running install serves it at `/llms.txt`, `/llms-full.txt` and `/api.md`.

## Repositories

Core is this repository: `index.php`, `core.php`, `route.php`, `Lib/`, `Theme/` and the core modules `admin`, `api`, `eventp`, `feed`, `input`, `process`, `schedule` and `user`.

Other modules are separate repositories under [github.com/emoncms](https://github.com/emoncms). In a local install they are cloned into `Modules/`, or into `/opt/emoncms/modules` and symlinked into `Modules/`. Examples: `app`, `dashboard`, `graph`, `device`, `config`, `backup`, `sync`, `postprocess`, `network`.

Commit changes in the repository that owns the file. Check with `git rev-parse --show-toplevel` from the file's folder.

Pull requests go to the `master` branch. `stable` is the release branch.

## Checks

```
composer install
composer test        # parallel-lint and phpcs, as run in CI
composer phpunit     # unit tests, no database needed
```

Integration and feature tests need MySQL, and feature tests need a running web server. See [tests/readme.md](tests/readme.md).

For a UI change, load the page in a browser and check the browser console for errors. For a change to the user guide, see [docs/developer/STYLE.md](docs/developer/STYLE.md).

## Code conventions

- Match the style of the surrounding code.
- Start controllers, models and views with `defined('EMONCMS_EXEC') or die('Restricted access');`.
- Check `$session['read']`, `$session['write']` or `$session['admin']` in each controller action. A session can come from a web login, an API key or a device key.
- Check that a record belongs to `$session['userid']` before reading or changing it.
- Use prepared statements for SQL. Cast ids with `(int)`.
- Escape user data in HTML output with `htmlspecialchars()`.
- Actions that change data and are called from the web interface should use POST.
- `$redis` is `false` when Redis is disabled. Code must work in both cases.
- Wrap UI text in `tr()` and add translations to `locale/<lang>.json`.
- Use Bootstrap 5 components and the shared theme classes. Avoid new CSS where a theme class exists.
- Do not add dependencies without discussing them in an issue first.
- Schema changes go in `*_schema.php`. **Update Database** applies them.

## Writing style

For commit messages, pull requests, code comments and documentation:

- British spelling in prose. US spelling in code and CSS identifiers.
- Plain, short, declarative sentences. No em dashes.
- Commit messages: one line, or a short list of bullet points, factual.

## Issues and pull requests

- Say that an agent was used, and which one.
- A person should read and check every issue and pull request before it is submitted.
- For a bug, give the Emoncms version, steps to reproduce and the expected and actual result.
- Keep pull requests small, with one change each. Say how the change was tested.
- Update the user guide in `docs` when a change affects what users see.

## Security

Do not report security problems in public issues or pull requests. Email the details as described in [SECURITY.md](SECURITY.md).

Do not commit credentials, API keys or personal data. Test data should come from the [testdataset](https://github.com/emoncms/testdataset) module or be made up.
