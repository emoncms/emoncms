# Install Emoncms on Raspberry Pi (Raspbian)

This guide will install the current full version of emoncms onto a Raspberry Pi running the Raspbian operating system.    
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually [move the operating system partition (root) to an USB HDD](http://openenergymonitor.org/emon/node/2386#comment-12200) or to lower the write frequency to the SD card by using the low-write mode.  
Before installing emoncms, it is essential that you have working version of Raspbian installed on your Raspberry Pi. If not, head over to [raspberrypi.org](https://www.raspberrypi.org/documentation/installation/installing-images/README.md) and follow their installation guide.

## Preparation

If your operating system was already installed, you may need to start by updating the system repositories:

    sudo apt-get update

Install all dependencies:

    sudo apt-get install apache2 mysql-server mysql-client php5 libapache2-mod-php5 php5-mysql php5-curl php-pear php5-dev php5-mcrypt php5-json git-core redis-server build-essential ufw ntp

During the installation you will be prompted to select a password for the **'MYSQL "root" user'**, and again re-enter it. Make a note of the password - you will need it later

Configure PHP Timezone:

    sudo nano /etc/php5/apache2/php.ini

and search for "date.timezone" (possibly line 865):

    [Date]
    ; Defines the default timezone used by the date functions.
    ; http://php.net/date.timezone
    ;date.timezone =

edit date.timezone to your appropriate timezone. PHP supported timezones are [listed here:](http://php.net/manual/en/timezones.php). For example:

    date.timezone = "Europe/Amsterdam"
    
Now save and exit

Install pecl dependencies (serial, redis and swift mailer):

    sudo pear channel-discover pear.swiftmailer.org
    sudo pecl install channel://pecl.php.net/dio-0.0.6 redis swift/swift
    
Add pecl modules to php5 config:
    
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/apache2/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/cli/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

Emoncms uses a front controller to route requests, modrewrite needs to be configured:

    sudo a2enmod rewrite
    
For `<Directory />` and `<Directory /var/www/>` change `AllowOverride None` to `AllowOverride All`. This should be on lines 7 and 11 of `/etc/apache2/sites-available/default`
    
    sudo nano /etc/apache2/sites-available/default

Save & exit, then restart the lamp server:

    sudo /etc/init.d/apache2 restart
    
### Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

First cd into the var directory:

    cd /var/

Set the permissions of the www directory to be owned by your username:

    sudo chown $USER www

Cd into www directory:

    cd www

Download emoncms using git:

    git clone https://github.com/emoncms/emoncms.git
    
Once installed you can pull in updates with:

    cd /var/www/emoncms
    git pull
    
### Create a MYSQL database

    mysql -u root -p

Enter the **'MYSQL "root" user'** password that you set above.
Then enter the sql to create a database:

    CREATE DATABASE emoncms;
    
Then add a user for the emoncms database and set permissions.
In the command below, we're creating the database 'user' called 'emoncms', and you should create a new secure password of your choice for that user.
Make a note of both the database 'user' & 'password' as you will need them later for adding to the settings.php file:

    CREATE USER 'emoncms'@'localhost' IDENTIFIED BY 'NEW_SECURE_PASSWORD';
    GRANT ALL ON emoncms.* TO 'emoncms'@'localhost';
    flush privileges;

Exit mysql by:

    exit
    
### Create data repositories for emoncms feed engines:

    sudo mkdir /var/lib/phpfiwa
    sudo mkdir /var/lib/phpfina
    sudo mkdir /var/lib/phptimeseries

    sudo chown www-data:root /var/lib/phpfiwa
    sudo chown www-data:root /var/lib/phpfina
    sudo chown www-data:root /var/lib/phptimeseries

### Set emoncms database settings

cd into the emoncms directory where the settings file is located:

    cd /var/www/emoncms/

Make a copy of default.settings.php and call it settings.php:

    cp default.settings.php settings.php

Open settings.php in an editor:

    nano settings.php

Update your settings to use your Database 'user' & 'password', which will allow emoncms to access the database:

    $server   = "localhost";
    $database = "emoncms";
    $username = "Database user";
    $password = "Database password";
    
Save and exit.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen. 
Create an account by entering your email and password and clicking register to complete.  
At this stage, check the Administration page - 'Setup > Administration' and note any messages reported. Also make a note of your 'Write API Key' from the 'Setup > My Account' page.

### Install Emonhub
    
    git clone https://github.com/emonhub/dev-emonhub.git ~/dev-emonhub && ~/dev-emonhub/install

Edit the emonhub configuration file, entering your emoncms 'Write API Key', and if necessary also your rfm2pi frequency, group & base id:

    nano /etc/emonhub/emonhub.conf

Save & exit, then edit your inittab file by adding a '#' to the beginning of the last line, so it reads;  
'# T0:23:respawn:/sbin/getty -L ttyAMA0 115200 vt100' - (without the quotes):

    sudo nano /etc/inittab
   
Save & exit, then edit your cmdline.txt file by changing the single line to;  
dwc_otg.lpm_enable=0 console=tty1 root=/dev/mmcblk0p2 rootfstype=ext4 elevator=deadline rootwait

    sudo nano /boot/cmdline.txt

At this stage, close down your Raspberry Pi and connect your RFM69Pi add on board, ensuring that it's positioned correctly (see the photos in the OEM shop pages).

**You should now have a fully working version of emoncms v9 installed & running on your Raspberry Pi, if at this stage you don't, then please check the emoncms log - 'Setup > Administration > Logger' or report the issue in the [OEM forum](http://openenergymonitor.org/emon/forum) giving as much detail as possible.**
