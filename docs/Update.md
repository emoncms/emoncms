# Updating Emoncms

The latest version of the emoncms master branch (todo: link stable release here) includes an automated update procedure that aims to be compatible with any standard debian based installation. The update process has been tested so far on raspbian stretch and Ubuntu 16.04 LTS. The update procedure requires that the emoncms service-runner and 'emonpi' repository is installed, see installation details below.

![emoncms_update.png](files/emoncms_update.png)

1. Click on Update Emoncms to start an update of emoncms core and associated emoncms modules.

2. Click on Update EmonHub if you have emonhub installed on your system in the base directory e.g /opt/emon/emonhub

3. If you have either a rfm69pi adapter board or emonpi hardware connected via serial on port /dev/ttyAMA0 the Update Firmware board can be used to update the firmware. Select the firmware type as applicable. (This feature is primarily designed for a emonpi or emonbase installation but could be expanded to provide further direct serial or USB-to-serial firmware updates).

4. To update only the emoncms MYSQL Database click on 'Update Database'. The update emoncms process includes this step as part of its automatic update process.

Update all can be used to run all the updates above sequentially.

**Update Log**

Once an update starts the update log is shown on the same page, allowing for tracking of progress and catching of any errors in the update process. 

If you encounter any problems as a result of running any of the above update options please attach the update log in any forum support request. 

---

### Installation of service-runner

The service-runner is a background process that is used to launch a variety of emoncms related scripts, it is used to launch the update script here.

[Manual installation of service-runner](https://github.com/emoncms/emoncms/blob/master/scripts/services/install-service-runner-update.md)

### Installation of emonpi repository

The 'emonpi' repository currently contains the emoncms and wider system installation and update scripts. Ignore the repository name for now as these scripts are designed for use on any debian based system. Install the emonpi repository in your installation's base directory. The new recommended location for this is /opt/emon.

To create this directory from new:

    sudo mkdir /opt/openenergymonitor
    sudo chown USER:USER /opt/openenergymonitor
    cd /opt/openenergymonitor
    git clone https://github.com/openenergymonitor/emonpi.git

Tell your emoncms installation where to find the openenergymonitor installation directory, open /var/www/emoncms/settings.php

    $openenergymonitor_dir = "/opt/openenergymonitor";

Tell your emoncms installation where to find the emoncms symlinked modules directory, open /var/www/emoncms/settings.php
    
    $emoncms_dir = "/opt/emoncms";
    
Install or move existing symlinked emoncms modules such as postprocess, backup and sync to:

    /opt/emoncms/modules
