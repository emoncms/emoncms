# Emoncms 9

[![Build Status](https://travis-ci.org/emoncms/emoncms.svg?branch=master)](https://travis-ci.org/emoncms/emoncms)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/fd6200361ad442a49651f1ebe10542c4)](https://www.codacy.com/app/emoncms/emoncms?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=emoncms/emoncms&amp;utm_campaign=Badge_Grade)

Emoncms is an open-source web application for processing, logging and visualising energy, temperature and other environmental data and is part of the [OpenEnergyMonitor project](http://openenergymonitor.org).

**Version 9 of emoncms has been developed by [Chaveiro](https://github.com/chaveiro/) with a significant list of improvements, see [forum thread with full change log](http://openenergymonitor.org/emon/node/11009)**

![Emoncms](docs/files/emoncms_graphic.png)

## Requirements

- PHP (5.6 - 7 recomended) 
- MYSQL (14.14 recomended) 
- Apache (2.4 recomended)
- Redis recommended*

_*If you can install redis then it is recommended to do so. The way redis is used in emoncms reduces disk write load prolonging disk life (noticably on SD cards). Some input-processors also require redis and fail silently if redis is not installed. Some environments such as shared hosting or as far as we have tried windows servers dont support redis hence why emoncms has a fall back mode that allows core operation without redis._

## Using Emoncms

**Important:** See appendix on differences between standard Emoncms and Emoncms.org

**1. Screencasts/Videos**

These screencasts where put together to accompany the [EmonPi/EmonBase setup guide](http://guide.openenergymonitor.org/setup) and do therefore contain a number of references that are EmonPi/EmonBase/RaspberryPi specific. They do however give a useful overview of some of the core parts of emoncms including configuring inputs, creating feeds and dashboards.

- [YouTube: Logging data locally with emoncms on the EmonPi/Emonbase](https://www.youtube.com/watch?v=8nVP0Hgkuuc)
- [YouTube: Viewing data and creating dashboards on the EmonPi/Emonbase](https://www.youtube.com/watch?v=eqN9rc9VnqI)

There are also two screencasts that are specific to optional modules that are included on the EmonPi/Emonbase standard SD card install but are not part of the standard install if you follow the installation guides below.

- [YouTube: Connecting to WIFI using the emoncms WIFI module on the EmonPi/Emonbase](https://www.youtube.com/watch?v=77WEj9Q6JEE)
- [YouTube: Data backup and import using emoncms backup module on the EmonPi/Emonbase](https://www.youtube.com/watch?v=5U_tOlsWjXM)

**2. From the Guide**

- [Guide: Creating daily kWh graphs](http://guide.openenergymonitor.org/setup/daily-kwh) - How to create daily kWh graphs from cumulative kWh electricity/heat energy feeds.
- [Guide: Daily Averages](http://guide.openenergymonitor.org/setup/daily-averages) - How to extract daily averages from temperature, humidity & power feeds.
- [Guide: Exporting CSV](http://guide.openenergymonitor.org/setup/export-csv) - How to use the graph module and feeds interface to export CSV data for use in 3rd party programs such as OpenOffice Calc or Excel.
- [Guide: Histograms](http://guide.openenergymonitor.org/setup/histograms) - How to use the histogram tool in the graph module.
- [Guide: Home Energy Monitor](https://guide.openenergymonitor.org/applications/home-energy) - example of configuring the MyElectric dashboard.
- [Guide: Solar PV Monitor](https://guide.openenergymonitor.org/applications/solar-pv/) - example of configuring the MySolar dashboard.

**3. Emoncms Terminology**

- **Inputs:** An incoming datasource. Each input has an associated "node" identifier and a "key" sub-identifier. Inputs are entry points, only the last value and time of the input is recorded. To record historic data a feed needs to be created from an input.
- **Input: Node:** A grouping identifier for inputs or feeds.
- **Input: Key:** A sub-identifier for items within each Node.
- **Input process list (or input processing):** A list of processes performed sequentially on each input value as it is received on that input.
- **Process:** A function that can be attached to the process list of an input to change the value or to save the value to a feed.
- **Feeds:** (Feed) A place where data is recorded, a time-series of datapoints. The standard time-series databases used by emoncms are PHPFina and PHPTimeSeries and where written as part of the emoncms project.

**4. Emoncms.org API Reference**

The following API references apply to emoncms.org they differ slightly to the API available on EmonPI/EmonBase installs, the API refrence for which can be found from the inputs and feed pages when logged in locally.

- [Input API reference](https://emoncms.org/site/api#input)
- [Feed API reference](https://emoncms.org/site/api#feed)

## Install

Recommended: 

* [Ubuntu / Debian Linux via git](docs/LinuxInstall.md)
* [Raspberry Pi](docs/RaspberryPi/readme.md)
  * [Pre built emonSD SD-card Image Download](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log)

Experimental:

* [Multi-platform using Docker Container](https://github.com/emoncms/emoncms-docker)

Other (less supported, not tested on latest versions of emoncms)

* [Shared Linux Hosting](docs/SharedLinuxHostingInstall.md)
* [Windows](docs/WindowsInstall.md) Emoncms is developed and tested on Linux only and so additional research, steps may be required that are not covered in this guide.

## Modules

Modules can be installed by downloading or git cloning into the emoncms/Modules folder. Be sure to check for database updates in Administration menu after installing new modules:

- [Graph module](https://github.com/emoncms/graph) advanced graphing module that integrates with the emoncms feed list, highly recommended, examples of use can be found in emoncms guide [[1]](http://guide.openenergymonitor.org/setup/daily-kwh)[[2]](http://guide.openenergymonitor.org/setup/daily-averages/)[[3]](http://guide.openenergymonitor.org/setup/export-csv/)[[4]](http://guide.openenergymonitor.org/setup/histograms)

- [Dashboards module](https://github.com/emoncms/dashboard), required for creating, viewing and publishing dashboards: 

- [App Module](https://github.com/emoncms/app.git) - Application specific dashboards e.g. MyElectric, MySolar
    
- [Config]( https://github.com/emoncms/config.git) - In-browser emonhub.conf editor and emonhub.log log viewer. git clone
    
- [Wifi Module]( https://github.com/emoncms/wifi.git) - [Wifi configuration interface designed for use on the emonPi](https://guide.openenergymonitor.org/setup/connect/)

There are many other modules such as the event module and openbem (open source building energy modelling module) that are available, check out the [Emoncms repo list](https://github.com/emoncms)

## Branches

* [master](https://github.com/emoncms/emoncms) - The latest and greatest developments. Potential bugs, use at your own risk! All pull-requests should be made to the *master* branch.  [EmonCMS 9.x forum thread](http://openenergymonitor.org/emon/node/11009)

* [stable](https://github.com/emoncms/emoncms/tree/stable) - emonPi/emonBase release branch, regularly merged from master. Slightly more tried and tested. [See change log](https://github.com/emoncms/emoncms/releases)

* ARCHIVE [low-write (v8.5)](https://github.com/emoncms/emoncms/tree/low-write) - Old emonpi/emonbase emoncms version ([July 15 emonSD ready-to-go SD card image](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log)). Low-write mode is now available in v9.0. The low write version of emoncms is designed for running on SD cards. This is a cut down version of emoncms supports only the phpfina and phptimeseries feed engines (no in built feed averaging or histograms) and a reduced input processor set. **Archived branch**


## Upgrade

* [Upgrading emoncms](docs/Upgrading.md)

## Data Backup

* [Backup](docs/Backup.md)
* [Raspberry Pi Backup / Restore Module](https://github.com/emoncms/backup) (emonPi / emonBase)

## Development

* [Emoncms Community Forum](https://community.openenergymonitor.org/c/emoncms)
* [V9 Development thread](http://openenergymonitor.org/emon/node/11009) (archive)

## Tools

* [PHPFina data file viewer](https://github.com/trystanlea/phpfinaview) - Easily explore phpfina timeseries feed engine data files directly without a full emoncms installation. Useful for checking backup's and archived data.

#### Design

*Note: due to ongoing development some docs may now be outdated*

- [Emoncms architecture](https://learn.openenergymonitor.org/electricity-monitoring/emoncms-internals/architecture)
- [Input processing](https://learn.openenergymonitor.org/electricity-monitoring/emoncms-internals/input-processing)
- [Developing a new Module](https://learn.openenergymonitor.org/electricity-monitoring/emoncms-internals/developing-a-new-module)
- [Global variables in Emoncms](https://learn.openenergymonitor.org/electricity-monitoring/emoncms-internals/global-variables)

**Emoncms timeseries database design (feed storage)**

- [Emoncms time series database development history](https://learn.openenergymonitor.org/electricity-monitoring/timeseries/History)
- [Variable interval time series](https://learn.openenergymonitor.org/electricity-monitoring/timeseries/Variable-interval)
- [Fixed interval time series](https://learn.openenergymonitor.org/electricity-monitoring/timeseries/Fixed-interval)
- [Fixed interval with averaging time series](https://learn.openenergymonitor.org/electricity-monitoring/timeseries/Fixed-interval-averaging)
- [Improving write performance with buffering](https://learn.openenergymonitor.org/electricity-monitoring/timeseries/Write-load-investigation)

#### Android App

[Google Play](https://play.google.com/store/apps/details?id=org.emoncms.myapps&hl=en_GB)

[GitHub Repo](https://github.com/emoncms/AndroidApp)

[Development Forum](https://community.openenergymonitor.org/c/emoncms/mobile-app)

## More information

- Cloud hosted platform - http://emoncms.org
- [OpenEnergyMonitor Forums](https://community.openenergymonitor.org)
- [OpenEnergyMonitor Labs page](http://openenergymonitor.org/emon/labs)

## Standard Emoncms and Emoncms.org

There are differences between the standard version of emoncms and the version of emoncms running on emoncms.org. This repository contains the code for the standard version of emoncms, currently in version 9. This is the version installed on the OpenEnergyMonitor SD Card that comes with the EmonPi and EmonBase and is recommended for all self-install versions of emoncms.

The emoncms.org version [Github: emoncms/emoncmsorg](http://github.com/emoncms/emoncmsorg) is a fork that is specific for multi-server installations. While both versions share the same roots the code for emoncms.org differs significantly to the standard version of emoncms, the user experience is intended to be similar but there are currently a number of differences in the API and look of the inputs and feeds interfaces as well as a reduced feature set in general on emoncms.org in order to ensure stability. In general development on emoncms.org moves slower than the standard emoncms for this reason.
