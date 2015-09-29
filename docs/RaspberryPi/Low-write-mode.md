##Enabling Low-write mode
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened. and it is therefore recommended that you eventually move the operating system partition (root) to an USB HDD or to lower the write frequency to the SD card by using the low-write mode.
###Preparation

Before following this guide, it is essential that you have a git installed, working version of emoncms installed on your Raspberry Pi, and because the low-write mode **is not compatible with PHPFIWA feeds**, it's important that any existing PHPFIWA feeds are deleted, otherwise the system will become unstable.

Update emoncms to current version

    cd /var/www/emoncms && git pull

Create a symlink to run feedwriter as a daemon and set permissions

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/feedwriter
    sudo chown root:root /var/www/emoncms/scripts/feedwriter
    sudo chmod 755 /var/www/emoncms/scripts/feedwriter
    sudo update-rc.d feedwriter defaults

###Enable Low-write mode in emoncms

    nano /var/www/emoncms/settings.php
    
In the section:
* **Redis**, change `$redis_enabled` from `false` to `true`  
* **Redis Low-write mode** change `enabled` from `false` to `true`, and _optionally_ change `sleep` to a number (in seconds) which emoncms must cache before writing to disk.  
* **Engine settings**, change `//,Engine::PHPFIWA // 6` to `Engine::PHPFIWA   // 6` to disable PHPFIWA being an option for future feed selection.

Save & exit, then reboot

    sudo reboot

In your browser, open `emoncms -> Setup -> Administration` (you may need to log-out & log-in again to see Administration)  
Under `Server Information`, the `Emoncms` section will be now extended to show the number of feed data points currently committed to the cache before being written to disk.  
_NOTE: this data does not browser auto-update, it's necessary to refresh your browser to see the current data_ 
