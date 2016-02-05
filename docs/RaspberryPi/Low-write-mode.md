##Enabling Low-write mode
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually move the operating system partition (root) to an USB HDD or to lower the write frequency to the SD card by using the low-write mode.

As a general guide;
* A default installation of emoncms writes on average over 7kB of data to the disk per second.
* This guide will reduce the average amount of data written to approximately 100Bytes per second or less.
* A further optional stage to protect the SD card is making the filesystem read-only. This is the best option when emoncms is deployed in a location where the electricity supply regularly fails or is interrupted (Guide to follow).

####Preparation

Before following this guide;

1. It is essential that emoncms was initially installed by following either the [Raspbian Jessie](readme.md) or [Raspbian Wheezy](install_Wheezy.md) installation guide, or you have used git to install a working version of emoncms on your Raspberry Pi

1. Because the low-write mode **is not compatible with PHPFIWA feeds**, it's important that any existing PHPFIWA feeds are deleted, otherwise the system will become unstable.  

Update emoncms to current version:

    cd /var/www/emoncms && git pull

####Changes to filesystem

    sudo nano /etc/fstab

comment out the line starting `/dev/mmcblk0p2...` with a # so it reads `# /dev/mmcblk0p2...` and at the very top of the file, add the following 4 lines of text:

    # Temporary mount points
    tmpfs /tmp tmpfs nodev,nosuid,size=30M,mode=1777 0 0
    tmpfs /var/log tmpfs nodev,nosuid,size=30M,mode=1777 0 0
    /dev/mmcblk0p2 / ext4 defaults,ro,noatime,errors=remount-ro 0 1

Reboot the system `sudo reboot`  
The filesystem will then restart in read-only mode, which allows us to disable the filesystem journaling.  
Run the following command:

`sudo tune2fs -O ^has_journal /dev/mmcblk0p2`

The command takes a short while to complete, then shutdown the system, and remove the power lead when the pi has ceased activity.

`sudo poweroff`

Reconnect power and allow the pi to reboot.  
Remount the filesystem as read-write:

`sudo mount -o remount,rw /dev/mmcblk0p2  /`

This will allow you to edit fstab:

`sudo nano /etc/fstab`

Delete the line `/dev/mmcblk0p2 / ext4 defaults,ro,noatime,errors=remount-ro 0 1` and also delete the # from the line starting `# /dev/mmcblk0p2` (which you commented out earlier).  
Save & exit.

#### Setup Logfile environment
As emoncms, apache, redis & MYSQL cannot create their own logfiles - only write to them, it's necessary to create a logfile framework in tmpfs (RAM) by running a script as follows:

```cd /var/www/emoncms/scripts/logger/ && sudo chmod +x install.sh```  
```sudo ./install.sh```

then reboot.

`sudo reboot`

#### Move PHP sessions to tmpfs (RAM)

Edit the php config file to direct php5 sessions to tmpfs (RAM):

`sudo nano /etc/php5/apache2/php.ini`

Find the line - `; session.save_path = "/var/lib/php5/sessions"` and replace it with - `session.save_path = "/tmp"` **(Raspbian Jessie)** OR  
Find the line - `; session.save_path = "/var/lib/php5"` and replace it with - `session.save_path = "/tmp"` **(Raspbian Wheezy)**

Save & exit.

#### Configure Redis
Configure redis to run without data persistence:

`sudo nano /etc/redis/redis.conf`

Comment out all redis saves:

    # save 900 1
    # save 300 10
    # save 60 10000

Save & exit:

    sudo service redis-server reboot

Ensure all redis databases have been removed from `/var/lib/redis` with: 
    
   rm -rf /var/lib/redis/*

#### Configure Feedwriter

Create a symlink to run feedwriter as a daemon and set permissions:

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/feedwriter
    sudo chown root:root /var/www/emoncms/scripts/feedwriter
    sudo chmod 755 /var/www/emoncms/scripts/feedwriter
    sudo update-rc.d feedwriter defaults

####Enable Low-write mode in emoncms

    nano /var/www/emoncms/settings.php

In the section:
* **Redis**, change `$redis_enabled` from `false` to `true`  
* **Redis Low-write mode** change `enabled` from `false` to `true`, and _optionally_ change `sleep` to a number (in seconds) which emoncms must cache before writing to disk.  
* **Engine settings**, change `//,Engine::PHPFIWA // 6` to `Engine::PHPFIWA   // 6` to disable PHPFIWA being an option for future feed selection.

Save & exit, then power off your Raspberry Pi:

    sudo poweroff

Once your Pi has completely stopped, disconnect the power lead, then reconnect it, to restart your Raspberry Pi.

In your browser, open `emoncms -> Setup -> Administration` (you may need to log-out & log-in again to see Administration)  
Under `Server Information`, the `Emoncms` section will be now extended to show the number of feed data points currently committed to the cache before being written to disk.  
_NOTE: this data does not browser auto-update, it's necessary to refresh your browser to see the current data_
