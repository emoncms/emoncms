# Install Emoncms v8 on Ubuntu / Debian Linux

This guide should work on most debian systems including Ubuntu. For installation guide on installing emoncms on a raspberrypi see raspberrypi installation guides.

## Install dependencies

You may need to start by updating the system repositories

    sudo apt-get update

Install all dependencies:

    sudo apt-get install apache2 mysql-server mysql-client php5 libapache2-mod-php5 php5-mysql php5-curl php-pear php5-dev php5-mcrypt php5-json git-core redis-server build-essential ufw ntp

Install pecl dependencies (serial, redis and swift mailer)

    sudo pear channel-discover pear.swiftmailer.org
    sudo pecl install channel://pecl.php.net/dio-0.0.6 redis swift/swift
    
Add pecl modules to php5 config
    
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/apache2/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=dio.so" > /etc/php5/cli/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

Emoncms uses a front controller to route requests, modrewrite needs to be configured:

    sudo a2enmod rewrite
    
For `<Directory />` and `<Directory /var/www/>` change `AllowOverride None` to `AllowOverride All`. This may be on lines 7 and 11 of `/etc/apache2/sites-available/default`. Modern versions of Ubuntu store these in the main config file: `/etc/apache2/apache2.conf`.
    
    sudo nano /etc/apache2/sites-available/default
    
or

    sudo nano /etc/apache2/apache2.conf

[Ctrl + X ] then [Y] then [Enter] to Save and exit.

Restart the lamp server:

    sudo service apache2 restart
    
### Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

First cd into the /var/www directory:

    cd /var/www/

Set the permissions of the html directory to be owned by your username:

    sudo chown $USER html

Cd into html directory

    cd html

Download emoncms using git:

**You may want to install one of the other branches of emoncms here, perhaps to try out a new feature set not yet available in the master branch. See the branch list and descriptions on the [start page](https://github.com/emoncms/emoncms)**

    git clone -b stable https://github.com/emoncms/emoncms.git
    
Once installed you can pull in updates with:

    cd /var/www/html/emoncms
    git pull
    
### Create a MYSQL database

    mysql -u root -p

Enter the mysql password that you set above.
Then enter the sql to create a database:

    mysql> CREATE DATABASE emoncms;
    
Then add a user for emoncms and give it permissions on the new database (think of a nice long password):

    mysql> CREATE USER 'emoncms'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD_HERE';
    mysql> GRANT ALL ON emoncms.* TO 'emoncms'@'localhost';
    mysql> flush privileges;

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

    cd /var/www/html/emoncms/

Make a copy of default.settings.php and call it settings.php

    cp default.settings.php settings.php

Open settings.php in an editor:

    nano settings.php

Update your database settings to use your new secure password:

    $username = "USERNAME";
    $password = "YOUR_SECURE_PASSWORD_HERE";
    $server   = "localhost";
    $database = "emoncms";
    
You will also want to modify SMTP settings and the password reset flag further down in the settings file.

Save (Ctrl-X), type Y and exit

### Install add-on emoncms modules
    
    cd /var/www/html/emoncms/Modules
    
    git clone https://github.com/emoncms/dashboard.git
    git clone https://github.com/emoncms/app.git
 
See individual module readme's for further information on individual module installation.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen. 

Create an account by entering your email and password and clicking register to complete.

#### Note: Browser Compatibility

**Chrome Ubuntu 48.0.2564.81** - developed with, works great.

**Chrome Windows 25.0.1364.172** - quick check revealed no browser specific bugs.

**Firefox Ubuntu 15.0.1** - no critical browser specific bugs, but movement in the dashboard editor is much less smooth than chrome.

**Internet explorer 9** - works well with compatibility mode turned off. F12 Development tools -> browser mode: IE9. Some widgets such as the hot water cylinder do load later than the dial.

**IE 8, 7** - not recommended, widgets and dashboard editor <b>do not work</b> due to no html5 canvas fix implemented but visualisations do work as these have a fix applied.

#### PHP Suhosin module configuration (Debian 6, not required in ubuntu)

Dashboard editing needs to pass parameters through HTTP-GET mechanism and on Debian 6 the max
allowable length of a single parameter is very small (512 byte). This is a problem for designing of dashboard
and when you exceed this threshold all created dashboard are lost...

To overcome this problem modify "suhosin.get.max_value_length" in /etc/php5/conf.d/suhosin.ini" to large
value (8000, 16000 should be fine).

#### Enable Multi lingual support using gettext

Follow the guide here step 4 onwards: [https://github.com/emoncms/emoncms/blob/master/docs/gettext.md](https://github.com/emoncms/emoncms/blob/master/docs/gettext.md#4-install-gettext)

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

    sudo service apache2 restart
    
## Install Logger

   See: https://github.com/emoncms/emoncms/tree/master/scripts/logger
