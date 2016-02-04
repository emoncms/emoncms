## Enabling MQTT
MQTT is a "lightweight" messaging protocol, which enables the publishing of data from Emoncms, as well as subscribing to data from other connected devices. The emonPi uses MQTT to transfer data from emonHub to Emoncms.

### Preparation

Before following this guide, it is essential that emoncms was initially installed by following either the [Raspbian Jessie](readme.md) or [Raspbian Wheezy](install_Wheezy.md) installation guide, or you have used git to install a working version of Emoncms & emonHub on your Raspberry Pi, as well as a MQTT message broker such as [Mosquitto](http://mosquitto.org/) installed and running.

#### Update Emoncms:

    cd /var/www/emoncms && git pull

### Enable MQTT in Emoncms

Ensure you have the latest settings file (backup your settings first!):

    cp /var/www/emoncms/default.settings.php /var/www/emoncms/settings.php

or if using an emonPi:

    cp /var/www/emoncms/default.emonpi.settings.php /var/www/emoncms/settings.php

Edit the settings file:

    nano /var/www/emoncms/settings.php

In the settings file in the **MQTT** section change `$mqtt_enabled` from `false` to `true`, and also change the `$mqtt_server` IP address to that of your MQTT server. On the emonPi the host is `localhost` and authentication is enabled: `user => emonpi` and `password => emonpimqtt2016`.

The `basetopic` option sets the base MQTT topic to which Emoncms subscribers. The default base topic is `emon` which means Enmoncms will subcribe to `emon/#`. In emonpi, this base topic needs to match the MQTT basetopic published from emonHub.

### Run Emoncms phpmqtt_input script

Create a symlink to run `scripts/phpmqtt_input` as a daemon and set permissions

    cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/mqtt_input
    sudo chown root:root /var/www/emoncms/scripts/mqtt_input
    sudo chmod 755 /var/www/emoncms/scripts/mqtt_input
    sudo update-rc.d mqtt_input defaults

## Emonpi only

#### Ensure packages are installed

Update emonhub

    cd ~/emonhub && git pull

In addition to Mosquitto MQTT server we will need:

    sudo apt-get install libmosquitto-dev
    sudo pecl install Mosquitto-alpha
    (​Hit enter to autodetect libmosquitto location)

If PHP extension config files `/etc/php5/cli/conf.d/20-mosquitto.ini` and `/etc/php5/apache2/conf.d/20-mosquitto.ini` don't exist then create with:

    sudo sh -c 'echo "extension=mosquitto.so" > /etc/php5/cli/conf.d/20-mosquitto.ini'
    sudo sh -c 'echo "extension=mosquitto.so" > /etc/php5/apache2/conf.d/20-mosquitto.ini'

### Enable MQTT in emonHub:

Ensure you have the latest emonub.conf config file (backup your settings first!):

    cp ~/emonhub/conf/emonpi.default.emonhub.conf ~/data/emonhub.conf

The new config file has MQTT authentication and the new node variable MQTT topic data structure turned on by default, see [emonHub config guide](http://github.com/openenergymonitor/emonhub/blob/emon-pi/configuration.md) for more info.

    sudo service emonhub restart

After restart emonHub should now be publishing to basetopic (default 'emon') using the new node variable topic data structure e.g `emon/emontx/power1`. We can check by subcribing to the base topic:

    mosquitto_sub -v -u 'emonpi' -P 'emonpimqtt2016' -t 'emon/#'


## Node format

Both emonbase & emonpi process MQTT differently, therefore refer to the appropriate section below. 

### Emonbase
#### emoncms as a publisher

Data from within emoncms can be published by adding the Publish to MQTT input process to one or more of the node inputs. In the process 'Text' box add the topic, for example; emoncms/solar

####emoncms as a subscriber

Unlike the above, the [basetopic] is hardcoded within emoncms, and takes the format emon/* - where * is the emoncms node number. For example, if data is published to topic emon/20 then emoncms will subscribe to that data, creating and updating node 20 in your inputs page.
Emoncms will also decode data in comma-delimited format, so for example; publishing values 657,899,5,776 to emon/20 will create Node 20, with 4 Key inputs which correspond with the 4 published comma-delimited values. Name labels can then be added to the key inputs in emoncms

### Emonpi
#### emoncms as a publisher

Data from within emoncms can be published by adding the `Publish to MQTT` input process to one or more of the node inputs.
In the process 'Text' box add the topic, for example; `house/power/solar`

#### emoncms as a subscriber

[basetopic] and user ID of the target Emocnms account can be set in settings.php. **Default basetopic = `emon`**

Data posted to `nodes/[nodeID/name]/[keyname (optional)]` is posted to Emoncms inputs where it can be logged to feeds e.g:

* `[basetopic]/emontx/power 10`
    * create an input from emonTx node called `power` with value `10`  
* `[basetopic]/10/power 10`
    * create an input from `node 10` called `power` with value `10`
* `[basetopic]/emontx 10`
    * create input from `emontx` with `key 0` of value `10`
* `[basetopic]/emontx 10,11,12`
    * create input from `emontx` with `key 0` of value `10`, `key 1` of value `11` and `key 2` of value `11`
