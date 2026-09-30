# Core concepts

Emoncms is an open source web application for processing, logging and visualising energy, temperature and other environmental data. It runs locally on the emonPi and emonBase, and as a hosted service at [emoncms.org](https://emoncms.org). You can use either or both.

This page introduces the main parts of Emoncms. See the [glossary](glossary.md) for short definitions.

## How data flows

Data passes through Emoncms in this order:

1. A device or script sends data to Emoncms. On the emonPi and emonBase, emonHub passes data from the hardware to Emoncms over MQTT.
2. Each value arrives as an **input**. An input holds only the latest value and its time.
3. **Input processing** acts on the input. It can scale, combine or convert the value, and log the result to a feed.
4. A **feed** stores the history as a time series.
5. **Graphs**, **apps** and **dashboards** show the feed data.

## Inputs

Inputs are created automatically when data arrives. Each input has a **node** name and a **key**, for example node `emontx4` and key `P1`. See [Inputs](inputs.md).

## Input processing

Input processing acts on each new input value before it is stored. Use it to calibrate values, add or subtract inputs, and convert power in watts to cumulative energy in kWh. Each input has its own process list, which runs in order. See [Inputs](inputs.md).

## Feeds

A feed stores a time series of values. Most feeds use one of two engines developed for Emoncms:

- **PHPFina** stores values at a fixed interval, for example every 10 seconds. It is the default and suits most monitoring data.
- **PHPTimeSeries** stores each value with its timestamp. It suits irregular data.

A **virtual feed** stores nothing. It calculates values from other feeds when they are requested. See [Feeds](feeds.md) and [Virtual feeds](virtual-feeds.md).

## Devices

The device module links inputs from the same node into a device. Device templates create the inputs, input processing and feeds for a known type of hardware in one step. See [Devices](devices.md).

## Viewing data

- **Graphs**: the graph module is the main feed viewer. It opens when you click a feed. It can compare feeds, calculate daily totals and averages, and export CSV. See [Graphs](graphs.md).
- **Apps**: ready-made dashboards for common applications, such as solar and battery systems and heat pumps. See [Apps](apps.md).
- **Dashboards**: build your own page from widgets and charts. Dashboards can be public. See [Dashboards](dashboards.md).

## Modules

Core Emoncms includes users, inputs, input processing, feeds and schedules. Other features are modules. The modules below are installed by default on the emonPi and emonBase.

| Module | Purpose | Source |
|---|---|---|
| Graph | Feed viewer | [emoncms/graph](https://github.com/emoncms/graph) |
| App | Ready-made dashboards | [emoncms/app](https://github.com/emoncms/app) |
| Dashboard | Dashboard builder | [emoncms/dashboard](https://github.com/emoncms/dashboard) |
| Device | Devices and templates | [emoncms/device](https://github.com/emoncms/device) |
| Config | emonHub configuration editor | [emoncms/config](https://github.com/emoncms/config) |
| Backup | Backup, restore and SD card import | [emoncms/backup](https://github.com/emoncms/backup) |
| Post Process | Process existing feed data | [emoncms/postprocess](https://github.com/emoncms/postprocess) |
| Sync | Upload or download feeds between servers | [emoncms/sync](https://github.com/emoncms/sync) |
| Network | WiFi and network settings | [emoncms/network](https://github.com/emoncms/network) |
| Usefulscripts | Maintenance scripts | [emoncms/usefulscripts](https://github.com/emoncms/usefulscripts) |

Optional modules include [Email reports](emailreport.md) and the [Demand shaper](demandshaper.md).

## emonSD and EmonScripts

emonSD is the software image for the emonPi and emonBase. It also runs on a standard Raspberry Pi. See [emonSD download](../emonsd/download.md).

The image is built with EmonScripts. EmonScripts also runs the updater on the Emoncms **Admin** page, and can install Emoncms on other Debian systems. See [Install](../emonsd/install.md).
