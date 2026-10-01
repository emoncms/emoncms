---
orphan: true
---

# Docs

The user guide is published at [docs.openenergymonitor.org/emoncms](https://docs.openenergymonitor.org/emoncms). Page order on the docs site is set in [index.rst](index.rst). Developer pages in [developer](developer) are not published there. See [STYLE.md](developer/STYLE.md) for how to write and publish the guide.

## Getting started

- [Getting started: emonPi and emonBase](intro-rpi.md): set up Emoncms and log your first feeds
- [Getting started: emoncms.org](intro-remote.md): use the hosted service
- [Core concepts](coreconcepts.md): main parts of Emoncms
- [Glossary](glossary.md): short definitions

## Posting data

- [Inputs](inputs.md): inputs and input processing
- [Devices](devices.md): device templates
- [Posting data](postingdata.md): HTTP input API, MQTT and encrypted input
- [AI assistants](agents.md): using an assistant with the API
- [Pulse counting](pulse-counting.md): record a pulse count input

## Storing data

- [Feeds](feeds.md): feeds page and feed engines
- [Virtual feeds](virtual-feeds.md): feeds calculated from other feeds
- [Post process](postprocess.md): process recorded feed data

## Viewing data

- [Graphs](graphs.md): graph module
- [Daily kWh](daily-kwh.md): daily, weekly and monthly kWh totals
- [Averages](daily-averages.md): hourly, daily, weekly and monthly averages
- [Histograms](histograms.md): time or energy at each value
- [Export CSV](export-csv.md): export feed data
- [Apps](apps.md): ready-made dashboards
- [Dashboards](dashboards.md): build your own dashboard

## Managing your system

- [My Account](account.md): login details, API keys and preferences
- [Admin](admin.md): system status, updates and users
- [Update](update.md): update the emonPi or emonBase
- [Backup and restore](import.md): backup module
- [Sync](sync.md): copy feed data between servers
- [Remote access](remoteaccess.md): reach your emonPi or emonBase from outside your network
- [Troubleshooting](troubleshooting.md): common problems

## Scheduling and control

- [Schedules](schedule.md): time windows for input processes and virtual feeds
- [Email reports](emailreport.md): weekly summary by email
- [DemandShaper](demandshaper.md): not in active development

## Developer

### Getting started

- [Contributing](../CONTRIBUTING.md): state of development and how to contribute
- [Development setup](developer/development.md): local install, test data, tests and tools
- [AGENTS.md](../AGENTS.md): guide for AI coding agents
- [Docs style](developer/STYLE.md): writing and publishing the user guide <!-- menu:hide -->

### Design

- [Architecture](developer/design/architecture.md)
- [Input processing](developer/design/input-processing.md)
- [Developing a new module](developer/design/developing-a-new-module.md)
- [Global variables](developer/design/global-variables.md)
- [CSS guide](developer/design/css-guide.md)
- [Bootstrap 5 migration](developer/design/bootstrap5-migration.md) <!-- menu:hide -->
- [Translation](../scripts/translation/readme.md)

### Time series storage

- [Fixed interval time series](developer/timeseries/Fixed-interval.md)
- [Variable interval time series](developer/timeseries/Variable-interval.md)
- [Time series engine history and write load](developer/timeseries/History.md)

### API

- Input and feed API reference: click **API Help** on the **Inputs** page or **Feed API Help** on the **Feeds** page of any Emoncms install.

### Tools

- [CLI](developer/CLI.md) <!-- menu:hide -->
- [User guide screenshots](../scripts/docs-screenshots/README.md)

### Releases

- [Release checklist](developer/release-checklist.md) <!-- menu:hide -->
- [12.0.0: Bootstrap 5](developer/notes/bootstrap5-release.md) <!-- menu:hide -->
