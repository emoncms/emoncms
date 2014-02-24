# Emoncms v7 (redis)

As part of recent work to improve the performance of emoncms because of high load's on emoncms.org redis was introduced to store feed and input meta data including last feed time and value fields which where causing significant write load on the server. This change benefits all installation types of emoncms whether emoncms.org or a raspberrypi as it siginficantly reduces the amount of disk writes. 

Using redis in this way leads to quite a big performance improvement. Enabling almost 5 times the request rate in benchmarking.

Blog post: [http://openenergymonitor.blogspot.co.uk/2013/11/improving-emoncms-performance-with_8.html](http://openenergymonitor.blogspot.co.uk/2013/11/improving-emoncms-performance-with_8.html)

To upgrade you will need redis server installed and the phpredis client:

    sudo apt-get install redis-server
    sudo pecl install redis
    
Add pecl redis module to php5 config
    
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

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

### Using MYSQL or PHPTimeSeries instead of Timestore

If your a familiar with mysql and want to use mysql to do your own queries and processing of the feed data you may want to select mysql as the default data store rather than timestore. The disadvantage of MYSQL is that it is much slower than timestore for common timeseries queries such as zooming through timeseries data.

There is also another feed engine called PHPTimeSeries which provides improved timeseries query speed than mysql but is still slower than timestore. Its main avantages is that it does not require additional installation of timestore as it uses native php file access, it also stores the data in the same data file .MYD format as mysql which means you can switch from mysql to phptimeseries by copying the .MYD mysql data files directly out of your mysql directory into the PHPTimeSeries directory without additional conversion.

To select either MYSQL or PHPTimeSeries instead of timestore as your default engine set the default engine setting in the emoncms settings.php file to:

    $default_engine = Engine::MYSQL;
    
or: 

    $default_engine = Engine::PHPTIMESERIES;

If you do not wish to use timestore you can skip to step 2 of the installation process.

If you want to try PHPTimeSeries see optional PHPTimeSeries step below.

# Installation

The following details how to install emoncms on linux from scratch including lamp server install and config.

## 1) Download, make and start timestore

    cd /home/username
    git clone https://github.com/TrystanLea/timestore
    cd timestore
    sudo sh install
    
**Note the adminkey** at the end as you will want to paste this into the emoncms settings.php file.

If the adminkey could not be found, it may be that timestore failed to start:

To check if timestore is running type:

    sudo /etc/init.d/timestore status
    
Start, stop and restart it with:

    sudo /etc/init.d/timestore start
    sudo /etc/init.d/timestore stop
    sudo /etc/init.d/timestore restart
    
To read the adminkey manually type:

    cat /var/lib/timestore/adminkey.txt
    
## (Optional) Create PHPTimeSeries data folder

If you wish to try the phptimeseries engine:

    sudo mkdir /var/lib/phptimeseries
    sudo chown www-data:root /var/lib/phptimeseries
    
## 2) Install Apache, Mysql and PHP (LAMP Server)
    
When installing mysql and the blue dialog appears enter a password for root user, note the password down as you will need it later.

    $ sudo apt-get install apache2
    $ sudo apt-get install mysql-server mysql-client
    $ sudo apt-get install php5 libapache2-mod-php5
    $ sudo apt-get install php5-mysql  
    $ sudo apt-get install php5-curl
    
## 3) Enable mod rewrite

Emoncms uses a front controller to route requests, modrewrite needs to be configured:

    $ sudo a2enmod rewrite
    $ sudo nano /etc/apache2/sites-enabled/000-default

Change (line 7 and line 11), "AllowOverride None" to "AllowOverride All".
That is the sections <Directory /> and <Directory /var/www/>.
[Ctrl + X ] then [Y] then [Enter] to Save and exit.


Restart the lamp server:

    $ sudo /etc/init.d/apache2 restart

## 4) Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

    $ sudo apt-get install git-core
    
First cd into the var directory:

    $ cd /var/

Set the permissions of the www directory to be owned by your username:

    $ sudo chown $USER www

Cd into www directory

    $ cd www

If you do not yet have emoncms installed, git clone to download:

    $ git clone https://github.com/emoncms/emoncms.git
    
If you do already have emoncms installed via the git clone command you can download the latest changes with:

    git pull

Alternatively download emoncms and unzip to your server:
[https://github.com/emoncms/emoncms](https://github.com/emoncms/emoncms)

## 5) Create a MYSQL database

    $ mysql -u root -p

Enter the mysql password that you set above.
Then enter the sql to create a database:

    mysql> CREATE DATABASE emoncms;

Exit mysql by:

    mysql> exit

## 6) Set emoncms database settings.

cd into the emoncms directory where the settings file is located

    $ cd /var/www/emoncms/

Make a copy of default.settings.php and call it settings.php

    $ cp default.settings.php settings.php

Open settings.php in an editor:

    $ nano settings.php

Enter in your database settings.

    $username = "USERNAME";
    $password = "PASSWORD";
    $server   = "localhost";
    $database = "emoncms";
    
If your using timestore enter the adminkey as copied in step 1 above:    
    
    $timestore_adminkey = "";
    
If your not using timestore set the default engine to your selected engine:

    $default_engine = Engine::MYSQL;
    
or

    $default_engine = Engine::PHPTIMESERIES;

Save (Ctrl-X), type Y and exit

## 7) In an internet browser, load emoncms:

    http://IP-ADDRESS/emoncms

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen. 

Create an account by entering your email and password and clicking register to complete.
<br><br>

#### PHP Suhosin module configuration (Debian 6, not required in ubuntu)

Dashboard editing needs to pass parameters through HTTP-GET mechanism and on Debian 6 the max
allowable length of a single parameter is very small (512 byte). This is a problem for designing of dashboard
and when you exceed this threshold all created dashboard are lost...

To overcome this problem modify "suhosin.get.max_value_length" in /etc/php5/conf.d/suhosin.ini" to large
value (8000, 16000 should be fine).

#### Enable Multi lingual support using gettext

Follow the guide here step 4 onwards: [http://emoncms.org/site/docs/gettext](http://emoncms.org/site/docs/gettext)

#### Configure PHP Timezone

PHP 5.4.0 has removed the timezone guessing algorithm and now defaults the timezone to "UTC" on some distros (i.e. Ubuntu 13.10). To resolve this:

Open php.ini

    sudo vi /etc/php5/apache2/php.ini

and search for "date.timezone"

    [Date]
    ; Defines the default timezone used by the date functions.
    ; http://php.net/date.timezone
    ;date.timezone =

edit date.timezone to your appropriate timezone:

    date.timezone = "Europe/Amsterdam"
    
PHP supported timezones are listed here: http://php.net/manual/en/timezones.php

Now save and close and restart your apache.

    sudo /etc/init.d/apache2 restart

# Upgrading

If your upgrading from emoncms version 5 up to 6 you will need to:

Install timestore as in step 1 above.

Install php curl:
    
    sudo apt-get install php5-curl

Run git pull in your emoncms directory

    cd /var/www/emoncms
    git pull
    
Create a fresh copy of default.settings.php with your mysql database settings and setting the timestore adminkey as in step 6 above. 

Log in with the administrator account (first account created)

Click on the *Admin* tab (top-right) and run database update.

Click on feeds, check that everything is working as expected, if your monitoring equipment is still posting you should see data coming in as usual.

## Converting existing feeds to timestore

So far we've got everything in place for using timestore but any existing feeds are still stored as mysql tables. To convert existing mysql feeds over to timestore a module has been written specifically for managing the conversion of the feeds, to download and run it:

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
- PlaneteDomo   https://github.com/PlaneteDomo
- Paul Reed     https://github.com/Paul-Reed
- thunderace    https://github.com/thunderace
- pacaj2am      https://github.com/pacaj2am
- Ynyr Edwards  https://github.com/ynyreds

