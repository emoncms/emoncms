# Development setup

Set up Emoncms locally to work on core or a module, with test data, tests and tools.

## Install

Choose one:

- **EmonScripts on Debian or Ubuntu.** The same scripts that build the emonSD image. Installs Apache, PHP, MySQL, Redis, Mosquitto, the services and the standard modules. See [Install](https://docs.openenergymonitor.org/emonsd/install.html).
- **emonSD on a Raspberry Pi.** Closest to what most users run. See [emonSD download](https://docs.openenergymonitor.org/emonsd/download.html).
- **Docker.** The [all-in-one container](https://hub.docker.com/r/alexjunk/emoncms) is the quickest way to try Emoncms. It is less suited to working on the code.

EmonScripts installs:

| Path | Contents |
|---|---|
| `/var/www/emoncms` | Core, served by Apache |
| `/var/www/emoncms/Modules/*` | Core modules, and modules cloned here (app, dashboard, graph, device, config) |
| `/opt/emoncms/modules/*` | Modules with background scripts (backup, sync, postprocess, network), symlinked into `Modules/` |
| `/opt/openenergymonitor` | EmonScripts, emonHub |
| `/var/opt/emoncms` | Feed data |
| `/var/log/emoncms` | Logs |

## Branches

Core and each module are separate git repositories. Work on `master` in each, and open pull requests against `master`. `stable` is the release branch that the updater installs.

Switch the install to `master` from **Setup > Admin > Components**, or with `git checkout master` in each repository. Then run **Update Database**.

## Settings

Copy `example.settings.ini` to `settings.ini` and set the database details. Settings not in `settings.ini` come from `default-settings.php`.

Useful for development:

```ini
[log]
level = 1          ; 1 INFO, 2 WARN, 3 ERROR
```

`display_errors` is on by default. Turn it off on a public server.

## Test data

The [testdataset](https://github.com/emoncms/testdataset) module holds a year of heat pump, solar and household data, and scripts that load it into an account ending today.

```
cd /opt/emoncms/modules
git clone https://github.com/emoncms/testdataset
cd testdataset
unzip phpfina.zip
cp scripts/example.settings.php scripts/settings.php   # set $userid
sudo php scripts/add_feeds_to_account.php
sudo php scripts/post_process.php
```

Run the two scripts again to bring the data up to date. To load it into a different account without editing `settings.php`, use `scripts/docs-screenshots/load-dataset.sh <userid>`.

Use a separate local account for development. Do not use real personal data in tests or screenshots.

## Checks and tests

```
php composer.phar install
php composer.phar run test          # lint and code style, as run in CI
php composer.phar run phpunit       # unit tests
```

Integration tests need MySQL. Feature tests call the API over HTTP at `http://localhost/emoncms`, or at `EMONCMS_BASE_URL`. See [tests/readme.md](../../tests/readme.md).

## Debugging

- Emoncms log: `tail -f /var/log/emoncms/emoncms.log`, or **Setup > Admin > Emoncms Log**.
- Apache log: `/var/log/apache2/error.log`.
- Services: `sudo systemctl status emoncms_mqtt feedwriter service-runner emonhub`.
- Restart `emoncms_mqtt` and `feedwriter` after changing code they load, such as process functions or feed engines.
- PHP OPcache can serve the old version of a file for a few seconds after a change.
- Browser console: the web interface calls the JSON API, so failed requests show there.

## Tools

| Tool | Use |
|---|---|
| `scripts/emoncms-cli admin:dbupdate` | Apply database schema changes. See [CLI](CLI.md) |
| `scripts/bootstrap5` | Build the reduced Bootstrap 5 CSS |
| `scripts/docs-screenshots` | Capture user guide screenshots |
| `scripts/translation` | Translation files |
| `/llms-full.txt`, `/api.md` | API reference from the running install |

## Next

- [Architecture](design/architecture.md)
- [Developing a new module](design/developing-a-new-module.md)
- [CSS guide](design/css-guide.md)
- [AGENTS.md](../../AGENTS.md), if you work with an AI coding agent
