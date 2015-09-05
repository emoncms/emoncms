# Install Emoncms on Raspberry Pi (Raspbian)

This guide will install the current full version of emoncms onto a Raspberry Pi running the Raspbian operating system.    
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually [move the operating system partition (root) to an USB HDD](http://openenergymonitor.org/emon/node/2386#comment-12200) or use the low-write version of emoncms.  
Before installing emoncms, it is essential you have a working version of Raspbian installed on your Raspberry Pi. If not, head over to [raspberrypi.org](https://www.raspberrypi.org/documentation/installation/installing-images/README.md) and follow their installation guide.

## Preparation

Start by updating the system repositories:

    sudo apt-get update

Install the dependencies:

    sudo apt-get install apache2 mysql-server mysql-client php5 libapache2-mod-php5 php5-mysql php5-curl php-pear php5-dev php5-mcrypt php5-json git-core redis-server build-essential ufw ntp

During the installation, you will be prompted to select a password for the 'MYSQL "root" user', and to confirm it by entering it a second time. Make a note of the password - you will need it later

Configure PHP Timezone:

    sudo nano /etc/php5/apache2/php.ini

and search for "date.timezone":

    [Date]
    ; Defines the default timezone used by the date functions.
    ; http://php.net/date.timezone
    ;date.timezone =

edit date.timezone to your appropriate timezone. PHP supported timezones are [listed here:](http://php.net/manual/en/timezones.php). For example:

    date.timezone = "Europe/Amsterdam"
    
Save and exit

Install the pecl dependencies (serial, redis and swift mailer):

    sudo pear channel-discover pear.swiftmailer.org
    sudo pecl install channel://pecl.php.net/dio-0.0.6 redis swift/swift
    
Add the pecl modules to php5 config:
    
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/apache2/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/cli/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

Issue the command:

    sudo a2enmod rewrite
    
For `<Directory />` and `<Directory /var/www/>` change `AllowOverride None` to `AllowOverride All`. This should be on lines 7 and 11 of `/etc/apache2/sites-available/default`
    
    sudo nano /etc/apache2/sites-available/default

Save & exit, then restart Apache:

    sudo /etc/init.d/apache2 restart
    
### Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

First, cd into the var directory:

    cd /var

Set the owner of the www directory:

    sudo chown $USER www

Cd into the www directory:

    cd www

Download emoncms:

    git clone https://github.com/emoncms/emoncms.git
    
Once installed, you can update emoncms with:

    cd /var/www/emoncms
    git pull
    
### Create a MYSQL database

    mysql -u root -p

When prompted, enter the 'MYSQL "root" user' password you were prompted for earlier in this procedure.
Create the emoncms database:

    CREATE DATABASE emoncms;
    
Add an emoncms database user and set that user's permissions.
In the command below, we're creating the database 'user' called 'emoncms', and you should create a new secure password of your choice for that user.
Make a note of both the database 'username' & 'password'. They will be inserted into the settings.php file in a later step:

    CREATE USER 'emoncms'@'localhost' IDENTIFIED BY 'new_secure_password';
    GRANT ALL ON emoncms.* TO 'emoncms'@'localhost';
    flush privileges;

Exit mysql:

    exit
    
### Create data repositories for emoncms feed engines:

    sudo mkdir /var/lib/phpfiwa
    sudo mkdir /var/lib/phpfina
    sudo mkdir /var/lib/phptimeseries

    sudo chown www-data:root /var/lib/phpfiwa
    sudo chown www-data:root /var/lib/phpfina
    sudo chown www-data:root /var/lib/phptimeseries

### Configure emoncms database settings

cd into the emoncms directory:

    cd /var/www/emoncms/

Make a copy of default.settings.php and call it settings.php:

    cp default.settings.php settings.php

Open settings.php in an editor:

    nano settings.php

Update your settings to use your Database 'user' & 'password', which will enable emoncms to access the database:

    $server   = "localhost";
    $database = "emoncms";
    $username = "emoncms";
    $password = "new_secure_password";
    
Save and exit.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically set up the database and you will be taken to the register/login screen. 
Create an account by entering your email and password and clicking register.  
At this stage, check the Administration page - 'Setup > Administration' and note any messages reported. Also make a note of your 'Write API Key' from the 'Setup > My Account' page.

### Install Emonhub
    
    git clone https://github.com/emonhub/dev-emonhub.git ~/dev-emonhub && ~/dev-emonhub/install

Edit the emonhub configuration file, entering your emoncms 'Write API Key', and if necessary, also your rfm2pi frequency, group & base id:

    nano /etc/emonhub/emonhub.conf

Save & exit. Edit /etc/inittab by adding a '#' to the beginning of the last line, so it reads;  
'# T0:23:respawn:/sbin/getty -L ttyAMA0 115200 vt100' - (without the quotes):

    sudo nano /etc/inittab
   
Save & exit. Edit /boot/cmdline.txt file by changing the line to;  
dwc_otg.lpm_enable=0 console=tty1 root=/dev/mmcblk0p2 rootfstype=ext4 elevator=deadline rootwait

    sudo nano /boot/cmdline.txt

At this stage, power off down your Raspberry Pi:

    poweroff

Once your Pi is fully powered off, connect your RFM69Pi add-on board, ensuring it's positioned correctly (see the photos in the OEM shop pages).

**You should now have a fully working version of emoncms v9 installed on your Raspberry Pi, if at this stage you don't, you may wish to check the emoncms log - 'Setup > Administration > Logger' or report the issue in the [OEM forum](http://openenergymonitor.org/emon/forum) giving as much detail as possible.**
