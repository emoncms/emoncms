# Emoncms 9

Emoncms is an open-source web application for processing, logging and visualising energy, temperature and other environmental data and is part of the [OpenEnergyMonitor project](http://openenergymonitor.org/emon).

**Version 9 of emoncms has been developed by [Chaveiro](https://github.com/chaveiro/) with a significant list of improvements, the EmonCMS 9.0 RC forum thread with full change log can be found here [http://openenergymonitor.org/emon/node/11009](http://openenergymonitor.org/emon/node/11009)**

![Emoncms](docs/files/emoncms_graphic.png)

## Branches

* [New: v8.5](https://github.com/emoncms/emoncms/tree/v8.5) - Version 8.5 focuses emoncms around a core set of timeseries storage engines: phpfina, phpfiwa and phptimeseries. The data request api has been improved and the way data requests are made make it easier to make cross feed comparisons. The myelectric and node modules have been removed, replaced with new optional modules that improve on the functionality of these modules called the app module and nodes module.

* [New: low-write (v8.5)](https://github.com/emoncms/emoncms/tree/low-write) - The latest version of the low write version of emoncms designed for running on SD cards. v8.5 includes the new data request api and default data viewer as found in the main v8.5 version, It also supports the new app and nodes module. Low-write-v8.5 is the version of emoncms installed on the emonpi. This is a cut down version of emoncms supports only the phpfina and phptimeseries feed engines (no in built feed averaging or histograms) and a reduced input processor set. Data is written to disk at spaced out intervals allowing datapoints to buffer and be written to disk in larger blocks.

**Optional modules**

Optional modules can be installed by downloading or git cloning into the emoncms/Modules folder. New for version v8.5 are:

- App provides application specific dashboards for emoncms: myelectric, mysolar, mysolar&wind, myheatpump. git clone https://github.com/emoncms/app.git

- Nodes provides a RFM12/69 focused alternative to the input module, with support for receiving and sending node data over the RFM network. The Nodes module has been developed to complement and integrate closely with emonhub node defenintions and configuration. git clone https://github.com/emoncms/nodes.git
    
- Config provides an in-browser emonhub.conf editor and emonhub.log log viewer. git clone https://github.com/emoncms/config.git
    
- Wifi provides an in emoncms wifi configuration interface designed for use on the emonpi. git clone https://github.com/emoncms/wifi.git

There are many other modules such as the event module and openbem (open source building energy modelling module) that are available, check out the emoncms repo list: https://github.com/emoncms


## Install

* Recommended: [Ubuntu / Debian Linux via git](docs/LinuxInstall.md)
* [Shared Linux Hosting](docs/SharedLinuxHostingInstall.md)
* [Windows](docs/WindowsInstall.md)

## Upgrade

* [Upgrading emoncms](docs/Upgrading.md)

## Backing up emoncms data

* [Backup](docs/Backup.md)

## Development

**Development plan overview: [Github: Emoncms development overview](https://github.com/emoncms/emoncms/issues/244)** (Out of date but pre v8.5)

**EmonView:** An open source python, flask, socketio, js web application for monitoring and control [https://github.com/emoncms/emonview](https://github.com/emoncms/emonview)


## Using emoncms

* [Github: Home Energy Monitor - Second half gives an example of how to configure emoncms to show an electric use dashboard](https://github.com/openenergymonitor/documentation/blob/master/Applications/HomeEnergyMonitor/HomeEnergyMonitor.md)
* [EmonTx v3: Quick start guide - an example of configuring the inputs of the standard (non watt hour calculating) firmware for the EmonTx v3 including My Electric dashboard configuration](http://openenergymonitor.org/emon/modules/emonTxV3)
* [EmonTH: Quick start guide - an example of configuring emoncms EmonTH inputs and creating temperature and humidity feeds](http://openenergymonitor.org/emon/modules/emonTH)
* [Blog post: An Example of configuring the new emoncms bargraph visualisation that uses accumulating watt hour data - part of the Monitoring SolarPV, Heatpump and house electric, EmonTx v2 system upgrade example](http://openenergymonitor.blogspot.co.uk/2014/08/monitoring-solarpv-heatpump-and-house.html)
* [An Example of more advanced custom dashboard setup](http://emoncms.org/site/docs/dashboards)
* [A list with screenshots of available visualisations](http://emoncms.org/site/docs/visualisations)


#### Design

Documentation hosted on openenergymonitor documentation github: 

- [Emoncms architecture](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/emoncms/architecture.md)
- [Input processing](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/emoncms/developinputproc.md)
- [Emoncms time series database development history](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/history.md)
- [Variable interval time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/variableinterval.md)
- [Fixed interval time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/fixedinterval.md)
- [Fixed interval with averaging time series](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/fixedintervalaveraging.md)
- [Improving write performance with buffering](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/TimeSeries/writeloadinvestigation.md)

#### Android App
- [Forum post: How to build an Energy Monitoring Android App part 1](http://openenergymonitor.org/emon/node/5250)
- [How to build an Energy Monitoring Android App P1 - Retrieving data from a remote server such as emoncms.org](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/AndroidApp/AndroidAppPart1.md)
- [How to build an Energy Monitoring Android App P2 - Drawing an Energy Monitoring display with java 2d canvas](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/AndroidApp/AndroidAppPart2.md)
- [Drawing a myelectric style bar chart](https://github.com/openenergymonitor/documentation/blob/master/BuildingBlocks/DrawingABarChart/DrawingABarChart.md)

## More information

- Official site - http://emoncms.org
- [OpenEnergyMonitor Forums](http://openenergymonitor.org/emon/forum)
- [OpenEnergyMonitor Labs page](http://openenergymonitor.org/emon/labs)

## IRC
You can also join us on our IRC channel #emon on irc.freenode.net.
    
## Developers
Emoncms is developed and has had contributions from the following people.

- Trystan Lea           https://github.com/trystanlea (principal maintainer)
- Chaveiro              https://github.com/chaveiro (principal developer of v9)
- Ildefonso Martínez    https://github.com/ildemartinez
- Matthew Wire          https://github.com/mattwire
- Baptiste Gaultier     https://github.com/bgaultier
- Paul Allen            https://github.com/MarsFlyer
- James Moore           https://github.com/foozmeat
- Lloyda                https://github.com/lloyda
- JSidrach              https://github.com/JSidrach
- Jramer                https://github.com/jramer
- Drsdre                https://github.com/drsdre
- Dexa187               https://github.com/dexa187
- Carlos Alonso Gabizó
- PlaneteDomo           https://github.com/PlaneteDomo
- Paul Reed             https://github.com/Paul-Reed
- thunderace            https://github.com/thunderace
- pacaj2am              https://github.com/pacaj2am
- Ynyr Edwards          https://github.com/ynyreds
- Jerome                https://github.com/Jerome-github
- fake-name             https://github.com/fake-name
