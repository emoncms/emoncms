# Emoncms 8

# Installation on Raspian/Debian/Ubuntu

Starting with version 8, it is possible to install emoncms using standard Debian package management. This installation path involves fewer
manual steps and controls for most dependency management automatically, and is therefore the recommended option if your system is compatible.

## Configuring apt.sources

In order to access the OpenEnergyMonitor apt repository you need to add a line to your apt.sources configuration file, which is located at: 

    /etc/apt/sources.list

You need to add the following line:

    deb http://emon-repo.s3.amazonaws.com wheezy unstable

## Install emoncms

You will need to update your system repositories:

    sudo apt-get update

And then install emoncms (all dependencies will also be intalled at this point):

    sudo apt-get install emoncms

The Debian package manager will now ask you a series of questions to configure emoncms. These are used to generate a valid settings.php file
for your installation.

Once the process completes, you need to enable emoncms in Apache:

    sudo a2ensite emoncms

Now is also a good time to ensure that mod_rewrite is also running:

    sudo a2enmod rewrite

Now restart Apache:

    sudo /etc/init.d/apache2 restart

## Install PECL modules (redis and swift mailer)

These modules are optional but will enhance the functionality of emoncms: redis will greatly reduce disk I/O (especially useful if you're running from an SD card). Swift mailer provides email :)

For instructions, see the general Linux installation steps below.

Note that it is not necessary to install the DIO (serial) library, as this is now provided by the `php5-dio` package in our apt-repository and will be included automatically if needed.

## Install add-on emoncms modules

You don't need to install all (or indeed any) of the optional add-on modules, but they may enhance the functionality or utility of your emoncms installation:

| Module  | Install from apt? |
| ------------- | ------------- |
| [Raspberry Pi](https://github.com/emoncms/raspberrypi) | `sudo apt-get install emoncms-module-rfm12pi` |
| [Event](https://github.com/emoncms/event) | manual only |
| [Notify](https://github.com/emoncms/notify) | manual only |
| [Energy](https://github.com/emoncms/energy) | manual only |
| [Report](https://github.com/emoncms/report) | manual only |
| [Open BEM](https://github.com/emoncms/openbem) | manual only |
| [Event](https://github.com/emoncms/event) | manual only |
| [Packetgen](https://github.com/emoncms/packetgen) | manual only |
| [MQTT](https://github.com/elyobelyob/mqtt) | manual only |

See the linked readme files for individual modules' installation instructions.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen.

Create an account by entering your email and password and clicking register to complete.


# Installation on Linux

## Install dependencies

You may need to start by updating the system repositories

    sudo apt-get update

Install all dependencies:

    sudo apt-get install apache2 mysql-server mysql-client php5 libapache2-mod-php5 php5-mysql php5-curl php-pear php5-dev php5-mcrypt git-core redis-server build-essential ufw ntp

Install pecl dependencies (serial, redis and swift mailer)

    sudo pear channel-discover pear.swiftmailer.org
    sudo pecl install channel://pecl.php.net/dio-0.0.6 redis swift/swift
    
Add pecl modules to php5 config
    
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/apache2/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/cli/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

Emoncms uses a front controller to route requests, modrewrite needs to be configured:

    $ sudo a2enmod rewrite
    $ sudo nano /etc/apache2/sites-enabled/000-default

Change (line 7 and line 11), "AllowOverride None" to "AllowOverride All".
That is the sections <Directory /> and <Directory /var/www/>.
[Ctrl + X ] then [Y] then [Enter] to Save and exit.

Restart the lamp server:

    $ sudo /etc/init.d/apache2 restart
    
### Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

First cd into the var directory:

    $ cd /var/

Set the permissions of the www directory to be owned by your username:

    $ sudo chown $USER www

Cd into www directory

    $ cd www

Download emoncms using git:

    $ git clone https://github.com/emoncms/emoncms.git
    
Once installed you can pull in updates with:

    git pull
    
### Create a MYSQL database

    $ mysql -u root -p

Enter the mysql password that you set above.
Then enter the sql to create a database:

    mysql> CREATE DATABASE emoncms;

Exit mysql by:

    mysql> exit
    
### Create data repositories for emoncms feed engine's

    sudo mkdir /var/lib/phpfiwa
    sudo mkdir /var/lib/phpfina
    sudo mkdir /var/lib/phptimeseries

    sudo chown www-data:root /var/lib/phpfiwa
    sudo chown www-data:root /var/lib/phpfina
    sudo chown www-data:root /var/lib/phptimeseries

### Set emoncms database settings.

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

You will also want to modify SMTP settings and the password reset flag further down in the settings file.

Save (Ctrl-X), type Y and exit

### Install add-on emoncms modules
    
    cd /var/www/emoncms/Modules
    
    git clone https://github.com/emoncms/raspberrypi.git
    git clone https://github.com/emoncms/event.git
    git clone https://github.com/emoncms/openbem.git
    git clone https://github.com/emoncms/energy.git
    git clone https://github.com/emoncms/notify.git
    git clone https://github.com/emoncms/report.git
    git clone https://github.com/emoncms/packetgen.git
    git clone https://github.com/elyobelyob/mqtt.git
 
See individual module readme's for further information on individual module installation.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen. 

Create an account by entering your email and password and clicking register to complete.

#### Note: Browser Compatibility

**Chrome Ubuntu 23.0.1271.97** - developed with, works great.

**Chrome Windows 25.0.1364.172** - quick check revealed no browser specific bugs.

**Firefox Ubuntu 15.0.1** - no critical browser specific bugs, but movement in the dashboard editor is much less smooth than chrome.

**Internet explorer 9** - works well with compatibility mode turned off. F12 Development tools -> browser mode: IE9. Some widgets such as the hot water cylinder do load later than the dial.

**IE 8, 7** - not recommended, widgets and dashboard editor <b>do not work</b> due to no html5 canvas fix implemented but visualisations do work as these have a fix applied.

# Shared Linux Hosting

Your shared hosting provider should already have a LAMP server installed. You may need to ask your hosting provider to enable mod_rewrite. It's unlikely that redis will be available (redis is used to improve performance through caching), but emoncms can be run without it.

To install emoncms on a shared server

1) Download the (currently rework branch) zip file from:

[https://github.com/emoncms/emoncms/archive/rework.zip]([https://github.com/emoncms/emoncms/archive/rework.zip])

Unzip to your shared server's public_html folder, rename the folder to emoncms.

2) Create a mysql database for your emoncms installation, note down its name, username and password.

3) In your shared hosting /home/username folder create a folder called emoncmsdata to hold your emoncms feed data. (Note: NOT public_html as the data files should not be publicly accessible).
Then create three folders within your emoncmsdata folder called: phpfiwa, phpfina and phptimeseries

4) In the emoncms app directory make a copy of default_settings.php and call it settings.php. Open settings.php and enter your mysql username, password and database. In the feed_settings section uncomment the datadir defenitions and set them to the location of each of the feed engine data folders on your system.

5) Thats it, emoncms should now be ready to use! 


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

# Unit Tests
EmonCMS uses PhpUnit for unit testing. This can be installed a number of ways.

Using pear:

    sudo pear config-set auto_discover 1
    sudo pear install pear.phpunit.de/PHPUnit

For other ways see the [documentation](http://phpunit.de/manual/3.7/en/installation.html)

To run the tests, once you have PhpUnit installed run:

    phpunit test.php

If you would like to generate a coverage report in HTML you can do

    phpunit --coverage-html ./coverage test.php

Then visit http://site.tld/coverage/index.html

    
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
- Jerome        https://github.com/Jerome-github
- fake-name     https://github.com/fake-name
