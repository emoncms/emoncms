## Enabling MQTT
MQTT is a "lightweight" messaging protocol, which enables the publishing of data from Emoncms, as well as subscribing to data from other connected devices. The emonPi uses MQTT to transfer data from emonHub to Emoncms.

### Preparation

Before following this guide, it is essential that emoncms was initially installed by following either the [Raspbian Jessie](readme.md) or [Raspbian Wheezy](install_Wheezy.md) installation guide, or you have used git to install a working version of Emoncms & emonHub on your Raspberry Pi, as well as a MQTT message broker such as [Mosquitto](http://mosquitto.org/) installed and running.

#### Update Emoncms:

    cd /var/www/emoncms && git pull

#### Ensure packages are installed

In addition to Mosquitto MQTT server we will need to install [mosquitto-debian-repository]( http://mosquitto.org/2013/01/mosquitto-debian-repository) and [Mosquitto-PHP library](https://github.com/mgdm/Mosquitto-PHP):

    sudo apt-get install libmosquitto-dev
    sudo pecl install Mosquitto-alpha
    (​Hit enter to autodetect libmosquitto location)
    
If you get the error: "E: Unable to locate package libmosquitto-dev" follow the instructions at the top of the [mosquitto Debian package install guide](http://mosquitto.org/2013/01/mosquitto-debian-repository). 

If PHP extension config files `/etc/php5/cli/conf.d/20-mosquitto.ini` and `/etc/php5/apache2/conf.d/20-mosquitto.ini` don't exist then create with:

    sudo sh -c 'echo "extension=mosquitto.so" > /etc/php5/cli/conf.d/20-mosquitto.ini'
    sudo sh -c 'echo "extension=mosquitto.so" > /etc/php5/apache2/conf.d/20-mosquitto.ini'


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

Install `phpmqtt_input` systemd unit script and make starts on boot: 

```
sudo cp /var/www/emoncms/scripts/mqtt_input.service /etc/systemd/system/mqtt_input.service
sudo systemctl daemon-reload
sudo systemctl enable mqtt_input.service
```

Start / stop / restart with:

```
sudo systemctl start mqtt_input
sudo systemctl stop mqtt_input    
sudo systemctl restart mqtt_input
```

View status / log snippet with:

`sudo systemctl status mqtt_input -n50`

*Where -nX is the number of log lines to view* 

Log can be viewed as text and standard text manipulation tools can be applied: 

`sudo journalctl -f -u mqtt_input -o cat | grep emonpi`

Or with a datestamp:

`sudo journalctl -f -u mqtt_input -o short`

There are lots of journalctrl output options: `short, short-iso, short-precise, short-monotonic, verbose,export, json, json-pretty, json-sse, cat`

To view `mqtt_info` in the emoncms log, change emoncms loglevel to `1` (info) in `settings.php` then restart `mqtt_input`. 

#### An alternative for systems not running systemd

On older operating systems, or those not running systemd, the mqtt_input script can be run as a init.d daemon instead of the above which uses a systemd unit script instead. Install either this or the above, **Not both!**

Create a symlink to run the MQTT Input script as a daemon and set permissions
```
cd /etc/init.d && sudo ln -s /var/www/emoncms/scripts/mqtt_input
sudo chown root:root /var/www/emoncms/scripts/mqtt_input
sudo chmod 755 /var/www/emoncms/scripts/mqtt_input
sudo update-rc.d mqtt_input defaults
```
## Node format

#### emoncms as a publisher

Data from within emoncms can be published by adding the `Publish to MQTT` input process to one or more of the node inputs.
In the process 'Text' box add the topic, for example; `house/power/solar`

#### emoncms as a subscriber

[basetopic] and user ID of the target Emocnms account can be set in settings.php. **Default basetopic = `emon`**, which mean Emoncms will subcribe to `emon/#` where # is any higher level topics.

E.g. Data posted to `emon/[nodeID/name]/[keyname (optional)]` is posted to Emoncms inputs where it can be logged to feeds e.g:

* `[basetopic]/emontx/power 10`
    * create an input from emonTx node called `power` with value `10`  
* `[basetopic]/10/power 10`
    * create an input from `node 10` called `power` with value `10`
* `[basetopic]/emontx 10`
    * create input from `emontx` with `key 0` of value `10`
* `[basetopic]/emontx 10,11,12`
    * create input from `emontx` with `key 0` of value `10`, `key 1` of value `11` and `key 2` of value `11`

*Multiple keys in CSV format can be posted to a topic creating multiple sequentially numbered keys for a node, however this is discouraged as it makes it difficult for other services to subcribe to the MQTT feed and renders the topic non human readable.*

## To enable MQTT posting from emonHub

**Note: you must be using the [emon-pi variant](https://github.com/openenergymonitor/emonhub) of emonHub for MQTT support**

### Update emonhub

    cd ~/emonhub && git pull

### Enable MQTT in emonHub:

Ensure you have the latest emonub.conf config file (backup your settings first!):

    cp ~/emonhub/conf/emonpi.default.emonhub.conf ~/data/emonhub.conf

The new config file has MQTT authentication and the new node variable MQTT topic data structure turned on by default, see [emonHub config guide](http://github.com/openenergymonitor/emonhub/blob/emon-pi/configuration.md) for more info. Here is an example of the MQTT section from emonPi default emonhub.conf:

```
[[MQTT]]

    Type = EmonHubMqttInterfacer
    [[[init_settings]]]
        mqtt_host = 127.0.0.1
        mqtt_port = 1883
        mqtt_user = emonpi
        mqtt_passwd = emonpimqtt2016

    [[[runtimesettings]]]
        pubchannels = ToRFM12,
        subchannels = ToEmonCMS,

        # emonhub/rx/10/values format
        # Use with emoncms Nodes module
        node_format_enable = 0
        node_format_basetopic = emonhub/

        # emon/emontx/power1 format - use with Emoncms MQTT input
        # http://github.com/emoncms/emoncms/blob/master/docs/RaspberryPi/MQTT.md
        nodevar_format_enable = 1
        nodevar_format_basetopic = emon/
```


    sudo service emonhub restart

After restart emonHub should now be publishing to basetopic (default 'emon') using the new node variable topic data structure e.g `emon/emontx/power1`. We can check by subcribing to the base topic:

    mosquitto_sub -v -u 'emonpi' -P 'emonpimqtt2016' -t 'emon/#'

*Note: the emon-pi variant of emonHub will work fine on non emonPi's. The emonPi SD card image will work just fine without the LCD as emonBase function*
