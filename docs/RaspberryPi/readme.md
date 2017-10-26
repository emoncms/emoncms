## Install Emoncms on Raspberry Pi (Raspbian Stretch)

This guide will install the current full version of emoncms onto a Raspberry Pi running the Raspbian Stretch operating system.

**Highly Recommended: A pre-built Raspberry Pi SD card image is available with Emoncms pre-installed & optimised for low-write. [SD card image download & change log repository](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log). Full image build guide/notes are available [here](https://github.com/openenergymonitor/emonpi/blob/master/docs/SD-card-build.md).**

An alternative (older) installation guide is avaliable for [Raspbian Jessie](jessie.md) - they are different, so ensure that you use the correct guide!  

Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually [move the operating system partition (root) to an USB HDD](USB_HDD.md) or to lower the write frequency to the SD card by enabling the [low-write mode.](Low-write-mode.md)  
Before installing emoncms, it is essential you have a working version of Raspbian Stretch installed on your Raspberry Pi. If not, head over to [raspberrypi.org](https://www.raspberrypi.org/documentation/installation/installing-images/README.md) and follow their installation guide.

### Preparation

Start by updating the system repositories and packages:
```
sudo apt-get update && sudo apt-get upgrade  
sudo apt-get dist-upgrade && sudo rpi-update
```

#### Raspberry Pi v3 Compatibility

This section only applies to Raspberry Pi v3 and later.  
To avoid UART conflicts, it's necessary to disable Pi3 Bluetooth and restore UART0/ttyAMA0 over GPIOs 14 & 15;

	sudo nano /boot/config.txt
	
Add to the end of the file

	dtoverlay=pi3-disable-bt

We also need to stop the Bluetooth modem trying to use UART

	sudo systemctl disable hciuart

See [RasPi device tree commit](https://github.com/raspberrypi/firmware/commit/845eb064cb52af00f2ea33c0c9c54136f664a3e4) for `pi3-disable-bt` and [forum thread discussion](https://www.raspberrypi.org/forums/viewtopic.php?f=107&t=138223)

### Installation

Install the dependencies:

    sudo apt-get install -y apache2 mariadb-server mysql-client php7.0 libapache2-mod-php7.0 php7.0-mysql php7.0-gd php7.0-opcache php7.0-curl php-pear php7.0-dev php7.0-mcrypt php7.0-common redis-server php-redis git-core build-essential ufw ntp

Install the pecl dependencies (swift mailer):

    sudo pear channel-discover pear.swiftmailer.org
    sudo pecl channel-update pecl.php.net
    sudo pecl install channel://pecl.php.net/dio-0.1.0 swift/swift

Add the modules to php7 config:

    sudo sh -c 'echo "extension=dio.so" > /etc/php/7.0/apache2/conf.d/20-dio.ini'
    sudo sh -c 'echo "extension=dio.so" > /etc/php/7.0/cli/conf.d/20-dio.ini'

Issue the command:

    sudo a2enmod rewrite

For `<Directory />` and `<Directory /var/www/>` change `AllowOverride None` to `AllowOverride All`. This should be on, or very close to lines 161 and 172 of `/etc/apache2/apache2.conf`

    sudo nano /etc/apache2/apache2.conf

Save & exit, then restart Apache:

    sudo systemctl restart apache2

### Install the emoncms application via git

Git is a source code management and revision control system but at this stage we use it to just download and update the emoncms application.

First, set the permissions for the www directory:

    sudo chown $USER /var/www

Cd into the www directory and git clone emoncms:

    cd /var/www && git clone -b stable https://github.com/emoncms/emoncms.git

### Setup the Mariadb server (MYSQL)

Firstly we should secure the database server, and then create a database and database user for emoncms to use;

The following configuration commands gives 'sudoers' Mariadb root privileges from within the local network, to administer all aspects of the databases and users. It also removes an 'example' database and user, which is no longer required. 

```
sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1'); DELETE FROM mysql.user WHERE User=''; DROP DATABASE IF EXISTS test; DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%'; FLUSH PRIVILEGES;"
``` 

Create the emoncms database using utf8 character decoding:

    sudo mysql -e "CREATE DATABASE emoncms DEFAULT CHARACTER SET utf8;"
    
Add an emoncms database user and set that user's permissions. In the command below, we're creating the database 'user' named 'emoncms', and you should create a new secure password of your choice for that user. Make a note of both the database 'username' ('emoncms') & the 'new_secure_password'. They will be inserted into the settings.php file in a later step:

    sudo mysql -e "CREATE USER 'emoncms'@'localhost' IDENTIFIED BY 'new_secure_password'; GRANT ALL ON emoncms.* TO 'emoncms'@'localhost'; flush privileges;"

### Create data repositories for emoncms feed engines:

    sudo mkdir /var/lib/{phpfiwa,phpfina,phptimeseries}

...and set their permissions

    sudo chown www-data:root /var/lib/{phpfiwa,phpfina,phptimeseries}

### Configure emoncms database settings

Make a copy of default.settings.php and call it settings.php:

    cd /var/www/emoncms && cp default.settings.php settings.php

Open settings.php in an editor:

    nano settings.php

Update your settings to use your Database 'user' & 'password', which will enable emoncms to access the database:

    $server   = "localhost";
    $database = "emoncms";
    $username = "emoncms";
    $password = "new_secure_password";
    
Further down in settings is an optional 'data structure store' - Redis, which acts as a cache for the data produced by emoncms, to ensure that it is efficiently written to disk. To activate Redis, change 'false' to 'true'. :

	//2 #### Redis
	$redis_enabled = true;

Save and exit.  
Create a symlink to reference emoncms within the web root folder:

    cd /var/www/html && sudo ln -s /var/www/emoncms


Set write permissions for the emoncms logfile:

`sudo touch /var/log/emoncms.log && sudo chmod 666 /var/log/emoncms.log`

To enable the emoncms user-interface to reboot or shutdown the system, it's necessary to give the web-server sufficient privilege to do so.  
Open the sudoers file :

    sudo visudo
    
and edit the `# User privilege specification` section to be :

```
# User privilege specification
root    ALL=(ALL:ALL) ALL
www-data   ALL=(ALL) NOPASSWD:/sbin/shutdown
```
    
Save & exit 

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

If you want Emoncms to redirect from web root i.e load Emoncms with `http://localhost` add reference in `index.php` and remove the default apache `index.html` test page:

	sudo su
	echo "<?php header('Location: ../emoncms'); ?>" > /var/www/html/index.php
	rm /var/www/html/index.html
	exit

The first time you run emoncms it will automatically set up the database and you will be taken to the register/login screen.
Create an account by entering your email and password and clicking register.  

Once you are logged in;  
* Check the Administration page - 'Setup > Administration' noting and acting upon any messages reported.
* Update your database - 'Setup > Administration > Update database'.
* Make a note of your 'Write API Key' from the 'Setup > My Account' page, and also ensure that the correct timezone is selected & saved.

### Install Emonhub

    git clone https://github.com/emonhub/dev-emonhub.git ~/dev-emonhub && ~/dev-emonhub/upgrade

Edit the emonhub configuration file, entering your emoncms 'Write API Key' and set the "local" emoncms address `url = http://localhost/emoncms` (emonhub sends to http://emoncms.org by default). Also set your RFM2Pi frequency, group & base id if necessary:

    nano /etc/emonhub/emonhub.conf

Save & exit.  
Edit the cmdline.txt file:

    sudo nano /boot/cmdline.txt

by changing the line to - `dwc_otg.lpm_enable=0 console=tty1 root=/dev/mmcblk0p2 rootfstype=ext4 elevator=deadline fsck.repair=yes rootwait`  

Disable serial console boot

    sudo systemctl stop serial-getty@ttyAMA0.service
    sudo systemctl disable serial-getty@ttyAMA0.service

At this stage, power off your Raspberry Pi:

    sudo poweroff

Once your Pi has stopped, disconnect the power lead and connect your RFM69Pi add-on board, ensuring it's positioned correctly (see the photos in the OEM shop pages).

**You should now have a fully working version of emoncms installed on your Raspberry Pi, if at this stage you don't, you may wish to check the emoncms log - 'Setup > Administration > Logger' or report the issue in the [OEM forum](https://community.openenergymonitor.org) giving as much detail as possible.**

### System Options
* [Move the operating system partition (root) to an USB HDD](USB_HDD.md)
* [Enabling low-write mode](Low-write-mode.md)
* [Enabling MQTT](MQTT.md)
* [Installing emoncms Modules](general.md#module-installation)
* [Updating emoncms](general.md#updating-emoncms-via-git)  
* [System Logs](general.md#system-logs)
