##Enabling Low-write mode
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened. and it is therefore recommended that you eventually move the operating system partition (root) to an USB HDD or to lower the write frequency to the SD card by using the low-write mode.
###Preparation

Before following this guide, it is essential that you have a git installed, working version of emoncms installed on your Raspberry Pi.

Update emoncms to current version

    cd /var/www/emoncms && git pull

Create a symlink to run feedwriter as a daemon and set permissions

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/feedwriter
    sudo chown root:root /var/www/emoncms/scripts/feedwriter
    sudo chmod 755 /var/www/emoncms/scripts/feedwriter
    sudo update-rc.d feedwriter defaults

###Enable Low-write mode in emoncms

    nano /var/www/emoncms/settings.php

In the section **Redis**, change `$redis_enabled` from `false` to `true`

In the section **Redis Low-write mode** change `enabled` from `false` to `true`

Save & exit, then reboot

    sudo reboot
