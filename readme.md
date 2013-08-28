# Emoncms v6 (timestore+)

See main site: http://emoncms.org

Emoncms is an open source energy visualisation web application, the main feature's include

## Input processing
Input processing allows for conversion and processing before storage, there are over 15 different input processes from simple calibration to power to kWh-per-day data, or histogram data.

## Visualisation
Zoom through large datasets with flot and ajax powered level-of-detail amazing super powered graphs!

## Visual dashboard editor
Create dashboards out of a series of widgets and visualisations with a fully visual drag and drop dashboard editor.

## Open Source
We believe open source is a better way of doing things and that our cloud based web applications should also be open source.

Emoncms is part of the OpenEnergyMonitor project. A project to build a fully open source hardware and software energy monitoring platform.

With Emoncms you can have full control of your data, you can install it on your own server or you can use this hosted service.

Emoncms is licenced under the GPL Affero licence (AGPL)

# v6 (timestore+)

Emoncms version 6 brings in the capability of a new feed storage engine called timestore.
Timestore is time-series database designed specifically for time-series data developed by Mike Stirling.

[mikestirling.co.uk/redmine/projects/timestore](mikestirling.co.uk/redmine/projects/timestore)

Timestore's advantages:

**Faster Query speeds**
With timestore feed data query requests are about 10x faster (2700ms using mysql vs 210ms using timestore).
*Note:* initial benchmarks show timestore request time to be around 45ms need to investigate the slightly slower performance may be on the emoncms end rather than timestore.

**Reduced Disk use**
Disk use is also much smaller, A test feed stored in an indexed mysql table used 170mb, stored using timestore which does not need an index and is based on a fixed time interval the same feed used 42mb of disk space. 

**In-built averaging**
Timestore also has an additional benefit of using averaged layers which ensures that requested data is representative of the window of time each datapoint covers.

# Installation and upgrading

## 1) Download, make and start timestore

    cd /home/pi
    git clone https://github.com/TrystanLea/timestore
    cd timestore
    sudo sh install
    
**Note the adminkey** at the end as you will want to paste this into the emoncms settings.php file.

The installer will start timestore, you can check that its running with:

    sudo /etc/init.d/timestore status
    
Start, stop and restart it with:

    sudo /etc/init.d/timestore start
    sudo /etc/init.d/timestore stop
    sudo /etc/init.d/timestore restart

## 2) Install (git clone) or upgrade (git pull) emoncms

In the /var/www/ folder:

    cd /var/www

If you do not yet have emoncms installed, run:

    git clone https://github.com/emoncms/emoncms.git
    
If you do already have emoncms installed via the git clone command you can download the latest changes with:

    git pull

Create a fresh settings.php file from default.settings.php

    cp /var/www/emoncms/default.settings.php /var/www/emoncms/settings.php

Enter your mysql database settings and timestore adminkey as copied above in to settings.php

**Upgrade raspberrypi module:** at this point if you are using the raspberrypi module, it would be worth updating that too.

Launch emoncms in your browser:

    http://IP-ADDRESS/emoncms

Log in with the administrator account (first account created)

Click on the *Admin* tab (top-right) and run database update.

Click on feeds, check that everything is working as expected, if your monitoring equipment is still posting you should see data coming in as usual.

## 3) Convert your feeds to timestore

So far we've got everything in place for using timestore but the feeds are still stored as mysql tables. To convert the feeds over to timestore based feeds there are several steps that need to be taken, a module has been written specifically for managing the conversion of the feeds, to download and run it:

    cd /var/www/emoncms/Modules

    git clone https://github.com/emoncms/converttotimestore
    
Again log in with the administrator account (first account created)
Click on the *Admin* tab (top-right) and run database update.

Navigate to the convert to timestore menu item in the dropdown menu titled Extras and follow the steps outlined.
    
    
## Need help?
See timestore forum discussion: [http://openenergymonitor.org/emon/node/2651](http://openenergymonitor.org/emon/node/2651)

## Upgrading from version 4.0

If your updating from an installation thats older than the 12th of April 2013, the process of upgrading should be much the same as the above. If you cant login in the last step, try adding the line
 
    $updatelogin = true;
    
to settings.php to enable a special database update only session, be sure to remove this line from settings.php once complete.

# Developers
Emoncms is developed and has had contributions from the following people.

- Trystan Lea		https://github.com/trystanlea (principal maintainer)
- Ildefonso Martínez	https://github.com/ildemartinez
- Matthew Wire		https://github.com/mattwire
- Baptiste Gaultier	https://github.com/bgaultier
- Paul Allen		https://github.com/MarsFlyer
- James Moore		https://github.com/foozmeat		
- Lloyda		https://github.com/lloyda
- JSidrach		https://github.com/JSidrach
- Jramer		https://github.com/jramer
- Drsdre		https://github.com/drsdre
- Dexa187		https://github.com/dexa187
- Carlos Alonso Gabizó

