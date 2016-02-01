##Enabling MQTT
MQTT is a "lightweight" messaging protocol, which enables the publishing of data from emoncms, as well as subscribing to data from
other connected devices.
###Preparation

Before following this guide, it is essential that emoncms was initially installed by following either the [Raspbian Jessie](readme.md) or [Raspbian Wheezy](install_Wheezy.md) installation guide, or you have used git to install a working version of emoncms on your Raspberry Pi, as well as a MQTT message broker such as [mosquitto](http://mosquitto.org/) installed and running.

Update emoncms to current version

    cd /var/www/emoncms && git pull

Create a symlink to run phpmqtt_input as a daemon and set permissions

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/mqtt_input
    sudo chown root:root /var/www/emoncms/scripts/mqtt_input
    sudo chmod 755 /var/www/emoncms/scripts/mqtt_input
    sudo update-rc.d mqtt_input defaults
    sudo service mqtt_input start

###Enable MQTT in emoncms

    nano /var/www/emoncms/settings.php

In the section **MQTT**, change `$mqtt_enabled` from `false` to `true`, and also change the `$mqtt_server` IP address to that of your mqtt
server.
Save & exit, then reboot

    sudo reboot

###Node format

####emoncms as a publisher

Data from within emoncms can be published by adding the `Publish to MQTT` input process to one or more of the node inputs.
In the process 'Text' box add the topic, for example; `emoncms/solar`

####emoncms as a subscriber

Data posted to `nodes/[nodeID/name]/[keyname (optional)]` is posted to Emoncms inputs where it can be logged to feeds e.g:

* `nodes/emontx/power 10` 
    * create an input from emonTx node called `power` with value `10`  
* `nodes/10/power 10` 
    * create an input from `node 10` called `power` with value `10`
* `nodes/emontx 10` 
    * create input from `emontx` with `key 0` of value `10`
* `nodes/emontx 10,11,12`
    * create input from `emontx` with `key 0` of value `10`, `key 1` of value `11` and `key 2` of value `11`

