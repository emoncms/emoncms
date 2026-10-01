# Emoncms

Emoncms is an open source web application for processing, logging and visualising energy, temperature and other environmental data. It is part of the [OpenEnergyMonitor project](https://openenergymonitor.org).

![Emoncms](emoncms_graphic.png)

Emoncms 12 moves the interface to Bootstrap 5. See the [release notes](docs/developer/notes/bootstrap5-release.md).

## Documentation

- User guide: [docs.openenergymonitor.org/emoncms](https://docs.openenergymonitor.org/emoncms)
- Docs contents, user guide and developer docs: [docs/README.md](docs/README.md)
- Development setup: [docs/developer/development.md](docs/developer/development.md)
- Glossary of terms such as input, node, feed and process: [glossary](https://docs.openenergymonitor.org/emoncms/glossary.html)

### API

Every install includes its API reference:

- **API Help** on the **Inputs** page and **Feed API Help** on the **Feeds** page.
- `/llms-full.txt` or `/api.md`: the full reference as Markdown, for AI assistants and other tools. `/llms.txt` is a short index.

The emoncms.org reference: [Input API](https://emoncms.org/site/api#input), [Feed API](https://emoncms.org/site/api#feed).

## Install

Emoncms is tested on Raspberry Pi OS and Ubuntu. It should work on other Debian systems. Shared hosting and XAMPP are not supported, because Emoncms needs background services. A small Linux VPS works well.

- [Install with EmonScripts](https://docs.openenergymonitor.org/emonsd/install.html)
- [Download the emonSD image](https://docs.openenergymonitor.org/emonsd/download.html) for a Raspberry Pi
- [Buy an SD card with emonSD installed](https://shop.openenergymonitor.com/emonsd-pre-loaded-raspberry-pi-sd-card/)

### Docker

An [all-in-one Docker container](https://hub.docker.com/r/alexjunk/emoncms) is available for amd64, arm64 and arm/v7. It is built from the Emoncms `stable` branch on Alpine Linux, and tagged with the Alpine and Emoncms versions. It includes MariaDB, Redis, Mosquitto, the graph, sync, backup, dashboard and app modules, and the `emoncms_mqtt`, `service-runner` and `feedwriter` services.

```
sudo docker pull alexjunk/emoncms
```

See [emoncms-docker.github.io](https://emoncms-docker.github.io).

## Requirements

- PHP 8.1 or later. CI tests PHP 8.1 to 8.4.
- PHP extensions: `mysqli`, `redis`, `gettext`, `mbstring`, `curl`, `gd`, `xml`.
- MySQL or MariaDB.
- Apache with `mod_rewrite`.
- Redis, recommended.
- Mosquitto, for MQTT input.

Redis reduces disk writes, which extends the life of SD cards. Emoncms runs without Redis where it is not available, but input processes marked as needing Redis do not work.

## Modules

Install a module by cloning it into the `Modules` folder, or into `/opt/emoncms/modules` with a symlink for modules that have background scripts. Run **Update Database** on the **Admin** page after installing a module.

Modules installed on the emonSD image:

- [Graph](https://github.com/emoncms/graph): feed viewer.
- [Device](https://github.com/emoncms/device): set up inputs and feeds from device templates.
- [Dashboard](https://github.com/emoncms/dashboard): dashboard builder.
- [App](https://github.com/emoncms/app): ready-made dashboards such as My Electric Flow and My Heatpump.
- [Config](https://github.com/emoncms/config): emonHub configuration editor and log viewer.
- [Network](https://github.com/emoncms/network): WiFi and network settings.
- [Backup](https://github.com/emoncms/backup): backup, restore and SD card import.
- [Sync](https://github.com/emoncms/sync): upload or download feeds between servers.
- [Post Process](https://github.com/emoncms/postprocess): process recorded feed data.
- [Usefulscripts](https://github.com/emoncms/usefulscripts): maintenance scripts.

Other modules include [Email reports](https://github.com/emoncms/emailreport) and the [DemandShaper](https://github.com/emoncms/demandshaper). See the [Emoncms repositories](https://github.com/emoncms).

## Branches

- [master](https://github.com/emoncms/emoncms): latest development. Open pull requests against `master`.
- [stable](https://github.com/emoncms/emoncms/tree/stable): release branch, used by emonPi and emonBase updates. See the [releases](https://github.com/emoncms/emoncms/releases).

## Contributing

Please read the [state of development and contributing page](CONTRIBUTING.md) before starting significant work. Then discuss your idea with us in the issue list, by email (hello@openenergymonitor.zendesk.com), or on a call (email us to arrange one). We are happy to talk ideas through from the start. Trystan & Glyn, OpenEnergyMonitor.

Issues and pull requests made with the help of an AI agent are welcome. See [AGENTS.md](AGENTS.md).

Report security problems privately. See [SECURITY.md](SECURITY.md).

## Tools

- [PHPFina data file viewer](https://github.com/trystanlea/phpfinaview): explore PHPFina feed data files without an Emoncms install. Useful for checking backups and archived data.
- Android app: [Google Play](https://play.google.com/store/apps/details?id=org.emoncms.myapps&hl=en_GB), [source](https://github.com/emoncms/AndroidApp), [forum](https://community.openenergymonitor.org/c/emoncms/mobile-app).

## More information

- [Emoncms.org](https://emoncms.org), hosted Emoncms
- [OpenEnergyMonitor forum](https://community.openenergymonitor.org)
- [OpenEnergyMonitor](https://openenergymonitor.org)

## Licence

Emoncms is released under the GNU Affero General Public License, version 3 or later. See [LICENSE.txt](LICENSE.txt) and [COPYRIGHT.txt](COPYRIGHT.txt).
