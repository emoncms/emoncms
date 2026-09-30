# MQTT

MQTT is a messaging protocol. On the emonPi and emonBase it passes data between emonHub, Emoncms and other software.

The emonPi and emonBase run a [Mosquitto](https://mosquitto.org) MQTT server on port 1883. Find the username and password on the [emonSD download](../emonsd/download.md) page.

## Topics

Emoncms uses the base topic `emon/`. Each value has its own topic:

```
emon/<node>/<key>
```

For example, `emon/emonpi/power1` is input `power1` of node `emonpi`.

The base topic is set in `/etc/emonhub/emonhub.conf` and in the `[mqtt]` section of `/var/www/emoncms/settings.ini`.

## Publishers

### emonHub

emonHub decodes data from the emonPi, the emonBase radio and other interfacers, and publishes each value to `emon/<node>/<key>`.

- Restart: `sudo systemctl restart emonhub`
- Log: **Setup > EmonHub**, or `tail -f /var/log/emonhub/emonhub.log`

The emonHub log shows every value published to MQTT.

### Emoncms

The **Publish to MQTT via Redis** input process publishes an input value to any topic, for example `house/power/solar`. Enter the topic in the process text box.

## Subscribers

### Emoncms MQTT service

The `emoncms_mqtt` service subscribes to `emon/#`. It posts each value to Emoncms as an input, with node and key from the topic. Any device or script that publishes to `emon/` with valid credentials creates inputs in Emoncms.

- Restart: `sudo systemctl restart emoncms_mqtt`
- Log: **Setup > Admin > Emoncms Log**, or `tail /var/log/emoncms/emoncms.log`

### emonPi LCD

The emonPi LCD service subscribes to the emonHub topics to show live values on the display.

- Restart: `sudo systemctl restart emonPiLCD`
- Log: `tail /var/log/emonpilcd/emonpilcd.log`

## Test from the command line

Install the Mosquitto clients:

```
sudo apt install -y mosquitto-clients
```

The examples below use `USER` and `PASS` in place of the MQTT credentials. Add `-h <host>` to connect to another machine.

Show all Emoncms messages. `#` is a wildcard:

```
mosquitto_sub -v -u USER -P PASS -t 'emon/#'
```

Show messages for one node:

```
mosquitto_sub -v -u USER -P PASS -t 'emon/emonpi/#'
```

Publish a test value. It appears as an input on the **Inputs** page:

```
mosquitto_pub -u USER -P PASS -t 'emon/test/power1' -m '100'
```

## Other clients

Any MQTT client can connect to the emonPi or emonBase IP address on port 1883, for example [MQTT Explorer](https://mqtt-explorer.com) on a computer, or an MQTT app on a phone.
