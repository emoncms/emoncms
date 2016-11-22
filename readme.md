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
- Redis OPTIONAL (2.2 - 3.0 recomended)


## Install

* [Ubuntu / Debian Linux via git](docs/LinuxInstall.md)
* [Multi-platform using Docker Container](https://github.com/emoncms/emoncms-docker)
* [Raspberry Pi](docs/RaspberryPi/readme.md)
  * [Pre built emonSD SD-card Image Download](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log)
* [Shared Linux Hosting](docs/SharedLinuxHostingInstall.md)
* [Windows](docs/WindowsInstall.md)

## Modules

Modules can be installed by downloading or git cloning into the emoncms/Modules folder. Be sure to check for database updates in Administration menu after installing new modules:

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


## Using Emoncms

* [Home Energy Monitor](https://guide.openenergymonitor.org/applications/home-energy)
* [Solar PV Monitor](https://guide.openenergymonitor.org/applications/solar-pv/)

#### Design

*Note: due to ongoing development some docs may now be outdated*

- [Emoncms architecture](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/emoncms/architecture.md)
- [Input processing](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/emoncms/developinputproc.md)
- [Emoncms time series database development history](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/history.md)
- [Variable interval time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/variableinterval.md)
- [Fixed interval time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/fixedinterval.md)
- [Fixed interval with averaging time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/fixedintervalaveraging.md)
- [Improving write performance with buffering](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/writeloadinvestigation.md)

#### Android App

[Google Play](https://play.google.com/store/apps/details?id=org.emoncms.myapps&hl=en_GB)

[GitHub Repo](https://github.com/emoncms/AndroidApp)

[Development Forum](https://community.openenergymonitor.org/c/emoncms/mobile-app)

## More information

- Cloud hosted platform - http://emoncms.org
- [OpenEnergyMonitor Forums](https://community.openenergymonitor.org)
- [OpenEnergyMonitor Labs page](http://openenergymonitor.org/emon/labs)
