# Emoncms 9

Emoncms is an open-source web application for processing, logging and visualising energy, temperature and other environmental data and is part of the [OpenEnergyMonitor project](http://openenergymonitor.org/emon).

**Version 9 of emoncms has been developed by [Chaveiro](https://github.com/chaveiro/) with a significant list of improvements, see [forum thread with full change log](http://openenergymonitor.org/emon/node/11009)**

![Emoncms](docs/files/emoncms_graphic.png)

## Branches

**Note:** We are currently reorganising the emoncms branches. The master branch is now the same as 9.x. 

* [master](https://github.com/emoncms/emoncms) - The latest and greatest developments. Potential bugs, use at your own risk!  [EmonCMS 9.x forum thread](http://openenergymonitor.org/emon/node/11009)

* [stable](https://github.com/emoncms/emoncms/tree/stable) - emonPi/emonBase release branch, regularly merged from master. Slightly more tried and tested. 

* [low-write (v8.5)](https://github.com/emoncms/emoncms/tree/low-write) - The old emonpi/emonbase emoncms version (July 15 ready-to-go SD card image). Low-write mode is now available in v9.0. The low write version of emoncms is designed for running on SD cards. This is a cut down version of emoncms supports only the phpfina and phptimeseries feed engines (no in built feed averaging or histograms) and a reduced input processor set. **Archived branch**

**Optional modules**

Optional modules can be installed by downloading or git cloning into the emoncms/Modules folder. Be sure to update check for database updates in Administration menu after installing new modules:

- Dashboards module, required for creating, viewing and publishing dashboards: 
https://github.com/emoncms/dashboard

- App provides application specific dashboards for emoncms: myelectric, mysolar, mysolar&wind, myheatpump https://github.com/emoncms/app.git
    
- Config provides an in-browser emonhub.conf editor and emonhub.log log viewer. git clone https://github.com/emoncms/config.git
    
- Wifi provides an in emoncms wifi configuration interface designed for use on the emonpi. git clone https://github.com/emoncms/wifi.git

There are many other modules such as the event module and openbem (open source building energy modelling module) that are available, check out the emoncms repo list: https://github.com/emoncms


## Install

* Recommended: [Ubuntu / Debian Linux via git](docs/LinuxInstall.md)
* [Raspberry Pi](docs/RaspberryPi/readme.md)
* [Shared Linux Hosting](docs/SharedLinuxHostingInstall.md)
* [Windows](docs/WindowsInstall.md)

## Upgrade

* [Upgrading emoncms](docs/Upgrading.md)

## Backing up emoncms data

* [Backup](docs/Backup.md)

## Development

**v9 Development [http://openenergymonitor.org/emon/node/11009](http://openenergymonitor.org/emon/node/11009)**

**EmonView:** An open source python, flask, socketio, js web application for monitoring and control [https://github.com/trystanlea/emonview](https://github.com/trystanlea/emonview)


## Using emoncms

* [Blog post: An Example of configuring the new emoncms bargraph visualisation that uses accumulating watt hour data - part of the Monitoring SolarPV, Heatpump and house electric, EmonTx v2 system upgrade example](http://openenergymonitor.blogspot.co.uk/2014/08/monitoring-solarpv-heatpump-and-house.html)

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

    
## Developers
Emoncms is developed and has had contributions from the following people.

- Trystan Lea           https://github.com/trystanlea (principal maintainer)
- Chaveiro              https://github.com/chaveiro (principal developer of v9)
- Glyn Hudson           https://github.com/glynhudson
- Paul Reed             https://github.com/Paul-Reed
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
