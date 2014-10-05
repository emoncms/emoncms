# Emoncms 8

A powerful open-source web-app for processing, logging and visualising energy, temperature and other environmental data.

## Installation on Raspian/Debian/Ubuntu

Starting with version 8, it is possible to install emoncms using standard Debian package management, and this is the recommended option if your system is compatible.

There are significant advantages, including fewer manual processes, built-in dependency management and ease of upgrade / configuration. 

It's also the most stable way of maintaining an emoncms installation because only formally tagged versions of the master branch are included in the [pkg-emoncms](https://github.com/Dave-McCraw/pkg-emoncms/) repository and uploaded to apt. 

Do not use this approach if you want to run nightly builds!

**Installation instructions are maintained in the [pkg-emoncms](https://github.com/Dave-McCraw/pkg-emoncms/) readme**.

## Install

* [Ubuntu / Debian Linux](docs/LinuxInstall.md)
* [Shared Linux Hosting](docs/SharedLinuxHostingInstall.md)
* [Windows](docs/WindowsInstall.md)
    
## Install Logger

    sudo pear channel-discover pear.apache.org/log4php
    sudo pear install log4php/Apache_log4php
    
ensure that log file has write permissions for www-data, pi and root.
    
    sudo chmod 660 emoncms.log 

# More information

- Official site - http://emoncms.org
- Forums - http://openenergymonitor.org/emon/forum

## IRC
You can also join us on our IRC channel #emon on irc.freenode.net.
    
# Developers
Emoncms is developed and has had contributions from the following people.

- Trystan Lea           https://github.com/trystanlea (principal maintainer)
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
