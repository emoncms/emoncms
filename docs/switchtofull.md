## Switching to full emoncms (master branch)

To switch from the low write load branch of emoncms to the full master branch of emoncms which does not have the low write load optimisations but does have the PHPFiwa and Histogram feed engines.

You may wish to do this if your running of a harddrive instead of an SD card.

Place the RaspberryPI in write mode:

    rpi-rw

Navigate to the emoncms directory
    
    cd /var/www/emoncms
    
Switch to the master branch:
    
    git checkout master
    
**You may want to install one of the other branches of emoncms here, perhaps to try out a new feature set not yet available in the master branch. See the branch list and descriptions on the [start page](https://github.com/emoncms/emoncms)**
    
It should give the following confirmation:

    Branch master set up to track remote branch master from origin.
    Switched to a new branch 'master'

Create the phpfiwa data directory:

    sudo mkdir /home/pi/data/phpfiwa
    sudo chown www-data:root /home/pi/data/phpfiwa
    
Reapply www-data ownership:

    sudo chown -R www-data:root /home/pi/data/phpfina
    sudo chown -R www-data:root /home/pi/data/phptimeseries
    sudo chown -R www-data:root /home/pi/data/phpfiwa
    
Repeat the same for phptimestore or timestore if you wish to use these engines.

Add a settings.php entry for phpfiwa, in feed_settings add:

    'phpfiwa'=>array(
        'datadir'=>'/home/trystan/Data/19July/phpfiwa/'
    )

Disable feedwriter.php at startup:

    sudo update-rc.d feedwriter remove

Stop feedwriter.php

    sudo service feedwriter stop

Put the system back into read-only mode:

    rpi-ro


    


    
