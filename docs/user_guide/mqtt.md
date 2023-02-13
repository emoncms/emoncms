# MQTT

We use MQTT (Message Queuing Telemetry Transport) as one way of passing data between different hardware devices and software components within the OpenEnergyMonitor ecosystem.

The emonPi and emonBase running our emonSD software stack includes a local [Mosquitto MQTT](http://mosquitto.org/) server. A device can connect to this server and publish data to a MQTT topic. A script on the emonPi/emonBase then subscribes and receives the data sent by the device.

This Mosquitto server is accessible on port 1883, the default username is `emonpi` and password `emonpimqtt2016`.

## MQTT Publishers

### emonHub

The emonHub python service decodes the data received from the emonPi/emonBase + RF nodes and publishes to the emonPi/emonBase's Mosquitto MQTT server using the following topic format:

Each data key (power) has its own MQTT topic as a sub-topic of the NodeID or NodeName. This MQTT topic structure makes it far easier to subscribe to a particular node/key of interest e.g. `emontx/power1` from another service.

    basetopic/node/keyname

*Note: the default base topic is `emon/` this is set in `/etc/emonhub/emonhub.conf` and `/var/www/emoncms/settings.ini`.*

Example:

    emon/emonpi/power1

The emonHub service can be restarted with `$ sudo systemctl restart emonhub`.

Latest log file entries can be viewed via the Emoncms web interface admin or with: `$ tail -f /var/log/emonhub/emonhub.log`. All the data currently being published to MQTT topic can be viewed in real-time in the EmonHub log.

### Emoncms Publisher

Data can be published to an MQTT topic using the `Publish to MQTT` Emoncms Input Process. In the Input process 'Text' box add the topic, for example: `house/power/solar`.

---

## MQTT Subscribers

### Emoncms MQTT Service

The Emoncms MQTT service subscribes to the MQTT base topic (default `emon/#`) and posts any data on this topic to Emoncms Inputs with the NodeName and KeyName taken from the MQTT topic and sub-topic name.

**Example:**

A power value published to `emon/emonpi/power1` would result in an Emoncms Input from `Node: emonpi` with `power1=XX`.

Data from any service (internal or external) that connect to the MQTT server (assuming authentication) and publishes to the base topic `emon/` will appear in Emoncms.

*Emoncms MQTT Service is running by default on the emonSD software stack*

The MQTT input service can be restarted using `$ sudo systemctl restart emoncms_mqtt`. The Emoncms MQTT service runs the [`emoncms_mqtt.php` script](https://github.com/emoncms/emoncms/tree/master/scripts/services/emoncms_mqtt).

Latest log file entries can be viewed via Emoncms web interface admin or with: `$ tail /var/log/emoncms/emoncms.log`

### EmonPiLCD Service

The [emonPi's python LCD Service Script](https://github.com/openenergymonitor/emonpi/blob/master/lcd/emonPiLCD.py) subscribes to the MQTT messages published by emonHub in order to obtain the real-time data to display on the emonPiLCD.

The emonPiLCD service can be restarted with: `$ sudo systemctl restart emonPiLCD`.

Latest log file entries can be viewed with: `$ tail /var/log/emonpilcd/emonpilcd.log`.

---

## Testing MQTT

### Install the mosquitto-clients package

To test from the command line you first need to install the `mosquitto-clients` package

```shell
 sudo apt install -y mosquitto-clients
```

### Testing MQTT From the command line

Note all example commands presume you are using the MQTT Broker on the emonPi/emonBase/emonSD. Change the username/password/host if not.

To view all MQTT messages subscribe to  `emon/#` base topic :

    $ mosquitto_sub -v -u 'emonpi' -P 'emonpimqtt2016' -t 'emon/#'

To view all MQTT messages for a particular node subscribe to sub-topic:

    $ mosquitto_sub -v -u 'emonpi' -P 'emonpimqtt2016' -t 'emon/emonpi/#'

*Note: `#` denotes a wild-card*

### Test publishing and subscribing on a test topic

Subscribe to test topic:

    $ mosquitto_sub -v -u 'emonpi' -P 'emonpimqtt2016' -t 'test'

Open *another shell window* to publish to the test topic :

    $mosquitto_pub -u 'emonpi' -P 'emonpimqtt2016' -t 'test' -m 'helloWorld'

If all is working we should see `helloWord`.

### View the data from a browser or other device

To avoid connecting via SSH alternately you could use [MQTTlens Chrome Extension](https://chrome.google.com/webstore/detail/mqttlens/hemojaaeigabkbcookmlgmdigohjobjm?hl=en) or any other MQTT client connected to the emonPi IP address on port 1883 with user name: `emonpi` and password: `emonpimqtt2016`.

There are also MQTT clients for your phone or tablet.
