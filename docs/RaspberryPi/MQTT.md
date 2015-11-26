##Enabling MQTT
MQTT is a "lightweight" messaging protocol, which enables the publishing of data from emoncms, as well as subscribing to data from 
other connected devices.
###Preparation

Before following this guide, it is essential that emoncms was initially installed by following the [Raspberry Pi installation guide](readme.md) or you have used git to install a working version of emoncms on your Raspberry Pi, 
as well as a MQTT message broker such as [mosquitto](http://mosquitto.org/) installed and running.

Update emoncms to current version

    cd /var/www/emoncms && git pull

Create a symlink to run phpmqtt_input as a daemon and set permissions

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/mqtt_input
    sudo chown root:root /var/www/emoncms/scripts/mqtt_input
    sudo chmod 755 /var/www/emoncms/scripts/mqtt_input
    sudo update-rc.d mqtt_input defaults

###Enable MQTT in emoncms

    nano /var/www/emoncms/settings.php

In the section **MQTT**, change `$mqtt_enabled` from `false` to `true`, and also change the `$mqtt_server` IP address to that of your mqtt 
server.
Save & exit, then reboot

    sudo reboot

###Node format
####emoncms as a publisher
Data from within emoncms can be published by adding the `Publish to MQTT` input process to one or more of the node inputs.
In the process 'Text' box add the topic, for example; emoncms/solar
####emoncms as a subscriber
Unlike the above, the subscriber topic is hardcoded within emoncms, and takes the format `rx/*` - where `*` is the emoncms node number. For example, if data is published to topic rx/20 then emoncms will subscribe to that data, creating and updating node 20 in your inputs page
