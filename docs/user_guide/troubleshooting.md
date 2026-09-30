# Troubleshooting

Start with the [community forum FAQ](https://community.openenergymonitor.org/t/frequently-asked-questions/3005).

## Inputs or feeds not updating

### Check the services

Go to **Setup > Admin > System Info** and check the **Services** list. `emonhub`, `emoncms_mqtt`, `feedwriter`, `redis-server` and `mosquitto` should all show **Active** and running.

![Services](img/emoncms_services.png)

To check a service via SSH:

```
sudo systemctl status emoncms_mqtt
```

Common causes of a stopped service:

- Incorrect configuration or installation.
- A full `/tmp` or `/var/log` partition.
- SD card corruption after a power cut.

`emoncms_mqtt` and `feedwriter` depend on `redis-server`. `emonhub` and `emoncms_mqtt` depend on `mosquitto`.

### Services running but no inputs

Check the emonHub log at **Setup > EmonHub**. A working log looks like the example below.

1. No `NEW FRAME : OK` lines means emonHub receives no data from the radio or the measurement board. Possible causes:
   - A fault on the emonPi measurement board.
   - A fault on a wireless node sending to an emonBase.
   - Wrong radio settings, such as frequency or network group. These are in emonhub.conf. Click **Edit Config** on the EmonHub page. See [emonHub configuration](../emonhub/configuration.md).
2. `NEW FRAME : OK` lines but no `MQTT Publishing:` lines means a problem with the MQTT interfacer. Compare it with the [default emonhub.conf](https://github.com/openenergymonitor/emonhub/blob/emon-pi/conf/emonpi.default.emonhub.conf).

Example of a working emonhub.log:

```
2020-02-21 16:02:33,236 INFO     MainThread EmonHub emonHub emon-pi variant v3-beta
2020-02-21 16:02:33,236 INFO     MainThread Opening hub...
2020-02-21 16:02:33,237 INFO     MainThread Logging level set to DEBUG
2020-02-21 16:02:33,237 INFO     MainThread Creating EmonHubJeeInterfacer 'RFM2Pi' 
2020-02-21 16:02:33,238 DEBUG    MainThread Opening serial port: /dev/ttyAMA0 @ 38400 bits/s
2020-02-21 16:02:35,243 INFO     MainThread RFM2Pi device firmware version: [RF12demo.14]
2020-02-21 16:02:35,244 INFO     MainThread RFM2Pi device current settings:  E i5 g210 @ 433 MHz q1
2020-02-21 16:02:35,245 INFO     MainThread Setting RFM2Pi calibration: 230V (1p)
2020-02-21 16:02:36,247 DEBUG    MainThread Setting RFM2Pi interval: 60
2020-02-21 16:02:36,247 DEBUG    MainThread Setting RFM2Pi pubchannels: ['ToEmonCMS']
2020-02-21 16:02:36,248 DEBUG    MainThread Setting RFM2Pi subchannels: ['ToRFM12']
2020-02-21 16:02:36,249 INFO     MainThread Creating EmonHubMqttInterfacer 'MQTT' 
2020-02-21 16:02:36,251 DEBUG    RFM2Pi     acknowledged command: > 1p
2020-02-21 16:02:36,253 DEBUG    MainThread Setting MQTT subchannels: ['ToEmonCMS']
2020-02-21 16:02:36,254 INFO     MainThread Setting MQTT node_format_enable: 1
2020-02-21 16:02:36,254 INFO     MainThread Setting MQTT nodevar_format_enable: 1
2020-02-21 16:02:36,255 INFO     MainThread Setting MQTT nodevar_format_basetopic: emon/
2020-02-21 16:02:36,256 INFO     MainThread Creating EmonHubEmoncmsHTTPInterfacer 'emoncmsorg' 
2020-02-21 16:02:36,257 DEBUG    MainThread Setting emoncmsorg pubchannels: ['ToRFM12']
2020-02-21 16:02:36,258 DEBUG    MainThread Setting emoncmsorg subchannels: ['ToEmonCMS']
2020-02-21 16:02:36,258 WARNING  MainThread Setting emoncmsorg apikey: obscured
2020-02-21 16:02:36,258 INFO     MainThread Setting emoncmsorg url: https://emoncms.org
2020-02-21 16:02:36,259 INFO     MainThread Setting emoncmsorg senddata: 0
2020-02-21 16:02:36,259 INFO     MainThread Setting emoncmsorg sendstatus: 1
2020-02-21 16:02:36,352 DEBUG    RFM2Pi     RFM2Pi broadcasting time: 16:02
2020-02-21 16:02:38,456 DEBUG    RFM2Pi     device settings updated: E i5 g210 @ 433 MHz q1
2020-02-21 16:02:38,559 DEBUG    RFM2Pi     7 NEW FRAME : OK 24 164 0 0 0 151 2 27 0 1 0 0 0 (-45)
2020-02-21 16:02:38,561 DEBUG    RFM2Pi     7 Timestamp : 1582300958.5596843
2020-02-21 16:02:38,561 DEBUG    RFM2Pi     7 From Node : 24
2020-02-21 16:02:38,562 DEBUG    RFM2Pi     7    Values : [16.400000000000002, 0, 66.3, 2.7, 1]
2020-02-21 16:02:38,562 DEBUG    RFM2Pi     7      RSSI : -45
2020-02-21 16:02:38,563 DEBUG    RFM2Pi     7 Sent to channel(start)' : ToEmonCMS
2020-02-21 16:02:38,563 DEBUG    RFM2Pi     7 Sent to channel(end)' : ToEmonCMS
2020-02-21 16:02:38,665 DEBUG    RFM2Pi     acknowledged command: > 0,16,2,0,0s
2020-02-21 16:02:38,763 INFO     MQTT       Connecting to MQTT Server
2020-02-21 16:02:38,767 DEBUG    RFM2Pi     confirmed sent packet size: -> 4 b
2020-02-21 16:02:38,866 INFO     MQTT       connection status: Connection successful
2020-02-21 16:02:38,867 DEBUG    MQTT       CONACK => Return code: 0
2020-02-21 16:02:38,871 DEBUG    RFM2Pi     8 NEW FRAME : OK 19 181 0 0 0 37 2 28 0 1 0 0 0 (-49)
2020-02-21 16:02:38,872 DEBUG    RFM2Pi     8 Timestamp : 1582300958.87131
2020-02-21 16:02:38,873 DEBUG    RFM2Pi     8 From Node : 19
2020-02-21 16:02:38,873 DEBUG    RFM2Pi     8    Values : [18.1, 0, 54.900000000000006, 2.8000000000000003, 1]
2020-02-21 16:02:38,874 DEBUG    RFM2Pi     8      RSSI : -49
2020-02-21 16:02:38,874 DEBUG    RFM2Pi     8 Sent to channel(start)' : ToEmonCMS
2020-02-21 16:02:38,874 DEBUG    RFM2Pi     8 Sent to channel(end)' : ToEmonCMS
2020-02-21 16:02:38,881 DEBUG    emoncmsorg Buffer size: 1
2020-02-21 16:02:38,969 INFO     MQTT       on_subscribe
2020-02-21 16:02:38,970 DEBUG    MQTT       Publishing: emon/emonth1/temperature 18.1
2020-02-21 16:02:38,977 DEBUG    MQTT       Publishing: emon/emonth1/external temperature 0
2020-02-21 16:02:38,978 DEBUG    MQTT       Publishing: emon/emonth1/humidity 54.900000000000006
2020-02-21 16:02:38,980 DEBUG    MQTT       Publishing: emon/emonth1/battery 2.8000000000000003
2020-02-21 16:02:38,981 DEBUG    MQTT       Publishing: emon/emonth1/pulsecount 1
2020-02-21 16:02:38,982 DEBUG    MQTT       Publishing: emon/emonth1/rssi -49
2020-02-21 16:02:38,984 INFO     MQTT       Publishing: emonhub/rx/19/values 18.1,0,54.900000000000006,2.8000000000000003,1,-49
2020-02-21 16:02:39,480 DEBUG    RFM2Pi     9 NEW FRAME : OK 22 175 0 0 0 47 2 28 0 1 0 0 0 (-44)
2020-02-21 16:02:39,481 DEBUG    RFM2Pi     9 Timestamp : 1582300959.4804919
2020-02-21 16:02:39,482 DEBUG    RFM2Pi     9 From Node : 22
2020-02-21 16:02:39,482 DEBUG    RFM2Pi     9    Values : [17.5, 0, 55.900000000000006, 2.8000000000000003, 1]
2020-02-21 16:02:39,483 DEBUG    RFM2Pi     9      RSSI : -44
2020-02-21 16:02:39,483 DEBUG    RFM2Pi     9 Sent to channel(start)' : ToEmonCMS
2020-02-21 16:02:39,484 DEBUG    RFM2Pi     9 Sent to channel(end)' : ToEmonCMS
2020-02-21 16:02:39,594 DEBUG    MQTT       Publishing: emon/emonth4/temperature 17.5
2020-02-21 16:02:39,595 DEBUG    MQTT       Publishing: emon/emonth4/external temperature 0
2020-02-21 16:02:39,597 DEBUG    MQTT       Publishing: emon/emonth4/humidity 55.900000000000006
2020-02-21 16:02:39,598 DEBUG    MQTT       Publishing: emon/emonth4/battery 2.8000000000000003
2020-02-21 16:02:39,600 DEBUG    MQTT       Publishing: emon/emonth4/pulsecount 1
2020-02-21 16:02:39,601 DEBUG    MQTT       Publishing: emon/emonth4/rssi -44
2020-02-21 16:02:39,602 INFO     MQTT       Publishing: emonhub/rx/22/values 17.5,0,55.900000000000006,2.8000000000000003,1,-44
2020-02-21 16:02:41,904 DEBUG    RFM2Pi     10 NEW FRAME : OK 10 111 2 226 1 0 0 0 0 220 90 84 220 1 0 155 5 1 0 0 0 0 0 0 0 0 0 (-53)
2020-02-21 16:02:41,905 DEBUG    RFM2Pi     10 Timestamp : 1582300961.904312
2020-02-21 16:02:41,906 DEBUG    RFM2Pi     10 From Node : 10
2020-02-21 16:02:41,906 DEBUG    RFM2Pi     10    Values : [623, 482, 0, 0, 232.6, 121940, 66971, 0, 0]
2020-02-21 16:02:41,907 DEBUG    RFM2Pi     10      RSSI : -53
2020-02-21 16:02:41,907 DEBUG    RFM2Pi     10 Sent to channel(start)' : ToEmonCMS
2020-02-21 16:02:41,908 DEBUG    RFM2Pi     10 Sent to channel(end)' : ToEmonCMS
2020-02-21 16:02:42,019 DEBUG    MQTT       Publishing: emon/emontx1/power1 623
2020-02-21 16:02:42,020 DEBUG    MQTT       Publishing: emon/emontx1/power2 482
2020-02-21 16:02:42,021 DEBUG    MQTT       Publishing: emon/emontx1/power3 0
2020-02-21 16:02:42,022 DEBUG    MQTT       Publishing: emon/emontx1/power4 0
2020-02-21 16:02:42,023 DEBUG    MQTT       Publishing: emon/emontx1/vrms 232.6
2020-02-21 16:02:42,023 DEBUG    MQTT       Publishing: emon/emontx1/e1 121940
2020-02-21 16:02:42,024 DEBUG    MQTT       Publishing: emon/emontx1/e2 66971
2020-02-21 16:02:42,025 DEBUG    MQTT       Publishing: emon/emontx1/e3 0
2020-02-21 16:02:42,026 DEBUG    MQTT       Publishing: emon/emontx1/e4 0
2020-02-21 16:02:42,028 DEBUG    MQTT       Publishing: emon/emontx1/rssi -53
2020-02-21 16:02:42,029 INFO     MQTT       Publishing: emonhub/rx/10/values 623,482,0,0,232.6,121940,66971,0,0,-53
```

### emonHub not running

Check the log at **Setup > EmonHub**. A common cause is an error in emonhub.conf. Check it against the [default emonhub.conf](https://github.com/openenergymonitor/emonhub/blob/emon-pi/conf/emonpi.default.emonhub.conf).

### Emoncms MQTT service not running

Check **Setup > Admin > Emoncms Log**, or via SSH:

```
tail /var/log/emoncms/emoncms.log
```

A common cause is a problem with MySQL, Redis or Mosquitto. Try a reboot.

### Ask for help

Post on the [community forum](https://community.openenergymonitor.org/). Include any errors from the logs and the server information from **Setup > Admin > System Info**. Click **Copy as Markdown** and paste the result into your post.

## Disk space

Check free space in the **Disk** section of **Setup > Admin > System Info**. The data partition is `/var/opt/emoncms`.

If a partition is full, ask for help on the forum as above.

## Incorrect system time

The emonPi and emonBase must have the correct time, set to UTC. They get the time from an NTP server, so they need an internet connection at boot. For long periods without internet, [add a hardware real time clock](../emonpi/modifications.md).

On the emonPi, press the LCD button until the `uptime` page shows the time.

To check the time via SSH:

```
timedatectl
```

`System clock synchronized: yes` shows that the time is set from NTP.

### Force a time update

1. Check that the emonPi or emonBase has an internet connection.
2. Reboot.
3. If the time is still wrong, connect via SSH ([credentials](../emonsd/download.md)) and restart the time service:

   ```
   sudo systemctl restart systemd-timesyncd
   ```

4. Run `timedatectl` again to check.

### Set the Emoncms timezone

The system time is UTC. Emoncms applies your timezone separately. To set it, go to **Setup > My Account** and change **Timezone**.

## Vrms reads twice the expected value

In North America, see [Use in North America](../emonpi/north-america.md). Step 3 covers software calibration.

## Reset a local password

Connect via SSH ([credentials](../emonsd/download.md)) and run:

```
php /opt/emoncms/modules/usefulscripts/resetpassword.php
```

Enter the user id (default 1), then a new password or press enter to generate one:

```
=======================================
EMONCMS PASSWORD RESET
=======================================
Select userid, or press enter for default:
Using default user 1
Enter new password, or press enter to auto generate:
Auto generated password: 9f7599c8da
```

If you have also forgotten the username, list the users in the database. Enter the MySQL password when prompted ([credentials](../emonsd/download.md)):

```
mysql -u emoncms -p emoncms -e "SELECT id, username, email FROM users;"
```

## Factory reset

```{warning}
A factory reset deletes all Emoncms data.
```

```
sudo /opt/openenergymonitor/EmonScripts/other/factoryreset
sudo reboot
```
