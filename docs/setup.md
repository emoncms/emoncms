# Using the SD card

## 1) Download the ready-to-go SD card image:

**Latest - emonSD-13-03-15.img.zip (1.3GB)**

[UK Mirror](http://openenergymonitor.org/files/emonSD-13-03-15.zip)

[USA Mirror](http://oem.aluminumalloyboats.com/oem/emonSD-13-03-15.zip)

	Latest image (13-03-15) includes support for Raspberry Pi 2 and latest emonHub supporting new RFM69Pi



**Older - emonSD-13-08-14.img.zip (975Mb)**

[UK Mirror 1](http://files.openenergymonitor.org/emonSD-13-08-14.img.zip)

[Europe Mirror 1](http://pizzacapri-vissenbjerg.dk/oem/emonSD-13-08-14.img.zip)

[US Mirror](http://oem.aluminumalloyboats.com/oem/emonSD-13-08-14.img.zip)


Many thanks to [Bo Herrmannsen (boelle)](http://openenergymonitor.org/emon/user/3149) for Europe mirror hosting, please DM to report broken link. 

 
*Please get in contact if you can help with hosting bandwidth or seeding a torrent for these image downloads. Any help is much appreciated.*

### 1a) Alternatively build it yourself:

[https://github.com/emoncms/emoncms/blob/bufferedwrite/docs/install.md](https://github.com/emoncms/emoncms/blob/bufferedwrite/docs/install.md)

## 2) Write the image to an SD card

### Linux

Start by inserting your SD card, your distribution should mount it automatically so the first step is to unmount the SD card and make a note of the SD card device name, to view mounted disks and partitions run:

    $ df -h

You should see something like this:

    Filesystem            Size  Used Avail Use% Mounted on
    /dev/sda6             120G   90G   24G  79% /
    none                  490M  700K  490M   1% /dev
    none                  497M  1.7M  495M   1% /dev/shm
    none                  497M  260K  497M   1% /var/run
    none                  497M     0  497M   0% /var/lock
    /dev/sdb1             3.7G  4.0K  3.7G   1% /media/sandisk

Unmount the SD card, change sdb to match your SD card drive:

    $ umount /dev/sdb1 

If the card has more than one partition unmount that also: 

    $ umount /dev/sdb2

Locate the directory of your downloaded emoncms image in terminal and write it to an SD card using linux tool *dd*:

**Warning: take care with running the following command that your pointing at the right drive! If you point at your computer drive you could lose your data!**

    $ sudo dd bs=4M if=emonSD-13-08-14.img of=/dev/sdb

### Windows 

The main raspberry pi sd card setup guide recommends Win32DiskImager, see steps for windows here: 
[http://elinux.org/RPi_Easy_SD_Card_Setup](http://elinux.org/RPi_Easy_SD_Card_Setup)
Select the image as downloaded above.

### Mac OSX 

See steps for Mac OSX as documented on the main raspberry pi sd card setup guide:
[http://elinux.org/RPi_Easy_SD_Card_Setup](http://elinux.org/RPi_Easy_SD_Card_Setup)
Select the image as downloaded above.
<br><br>

## 3) Posting to Emoncms.org

If you just want to post data to emoncms.org you can configure the RaspberryPi by inserting the SD Card in your computer and editing the emonhub.conf file on the boot partition.

In the 'Reporters' section of emonhub.conf enter the write apikey from your emoncms.org and account and emoncms.org in the URL as shown below. 

In the 'Interfaces' section set the group and frequency to match your RFM12Pi adapter board and sensor node network.

No need to chage the 'Nodes' section. 

Example emonhub.conf for posting to emoncms.org: 

```python

pi@raspberrypi ~ $ cat /boot/emonhub.conf
# SPECIMEN emonHub configuration file
# Note that when installed from apt, a new config file is written 
# by the debian/postinst script, so changing this file will do 
# nothing in and of itself.

# Each Interfacer and each Reporter has
# - a [[name]]: a unique string
# - a type: the name of the class it instantiates
# - a set of init_settings (depends on the type)
# - a set of runtimesettings (depends on the type)
# Both init_settings and runtimesettings sections must be defined,
# even if empty. Init settings are used at initialization,
# and runtime settings are refreshed on a regular basis.

# All lines beginning with a '#' are comments and can be safely removed.

#######################################################################
#######################    emonHub  settings    #######################
#######################################################################
[hub]

# loglevel must be one of DEBUG, INFO, WARNING, ERROR, and CRITICAL
# see here : http://docs.python.org/2/library/logging.html
loglevel = WARNING


#######################################################################
#######################        Reporters        #######################
#######################################################################
[reporters]

# This reporter sends data to emonCMS
[[emonCMS]]
    Type = EmonHubEmoncmsReporter
    [[[init_settings]]]
    [[[runtimesettings]]]
        url = http://emoncms.org
        apikey = xxxxxxxxxxxxxxxxxxxxxxxxxxxxx



#######################################################################
#######################       Interfacers       #######################
#######################################################################
[interfacers]

# This interfacer manages the RFM2Pi module
[[RFM2Pi]]
    Type = EmonHubJeeInterfacer
    [[[init_settings]]]
        com_port = /dev/ttyAMA0

#un-comment for use with RFM69CW RFM12Pi        
        # com_baud = 57600 

# set to match RFM12Pi and sensor node network     
    [[[runtimesettings]]] 
        group = 210
        frequency = 433
        baseid = 15
        #interval=300 #un-comment to post time to emonGLCD every 5 min


#######################################################################
#######################          Nodes          #######################
#######################################################################
[nodes]

# List of nodes by node ID
# 'datacode' is default for node and 'datacodes' are per value data codes.
# if both are present 'datacode' is ignored in favour of 'datacodes'
[[99]]
	datacode = h
	datacodes = l, h, h, h,

```

Insert the SD card into the Pi, connect the RFM12Pi taking care to line up pin 1 on the GPIO header and power up the Pi. 

That's it, if you have sensor nodes sending data, inputs should start appearing in the inputs section your emoncms account in a few seconds.

Return to the OpenEnergyMonitor Guide to setup your sensor nodes and map the inputs to create feeds and build your dashboard in emoncms: http://openenergymonitor.org/emon/guide


## 3a) Recording data locally and/or posting to emoncms.org

Insert SD card and power up. It usually takes a minute to boot up the ACT light on the Pi should be actively flickering for the first minute.

Find the raspberrypi's IP address on your network, this can usually be found in your router status page. Alternatively there's a useful Android/iPhone app called Fing that can be used to scan for devices on a network.

Login to the raspberrypi with SSH (Putty is a useful tool to do this on windows).

    $ user@user:~$ ssh pi@192.168.1.XX
    $ pi@192.168.1.70's password:

The defaut password is password: 'raspberry'

The local installation of emoncms is disabled as default. To enable it first put the os partition into write mode with

    $ rpi-rw
    
and then run:
    
    $ localemoncms-enable

Note: to disable again: $ localemoncms-disable

In your internet browser window, enter the ip address of the raspberrypi. This should bring up the emoncms login page. 

Login with the default admin account name **raspberry** and password **raspberry**.

Its recommended that you change the default admin account name, password and email once you login.

The emoncms installation is ready to use and can be used much in the same way as an account on emoncms.org.

Also installed on the SD card is emonhub. Emonhub can be used to receive serial data from an attached rfm12pi adapter board and forward that data to the local emoncms install or/and forward the data to a remote emoncms account.

To configure emonhub to post to the local installation of emoncms note down the write apikey that is displayed on the account page.

Open the emonhub config file for editing:
    
   $ nano /boot/emonhub.conf

In the Reporters section enter the write apikey of your local emoncms account and in the Listeners section set the group and frequency of your rfm12pi adapter board and rf network.

Save and exit nano text editor using [Ctrl + X] then [Y] and [Enter]

Set the raspberrypi os back into read-only mode.

    $ rpi-ro

That's it, if you have sensor nodes sending data, inputs should start appearing in the inputs section your emoncms account in a few seconds.

Return to the OpenEnergyMonitor Guide to setup your sensor nodes and map the inputs to create feeds and build your dashboard in emoncms: http://openenergymonitor.org/emon/guide

**Emonhub reporters config example for posting data to both the local buffered write installation and emoncms.org**

    [reporters]

    [[emonCMS_local]]
        Type = EmonHubEmoncmsReporter
        [[[init_settings]]]
        [[[runtimesettings]]]
            url = http://localhost/emoncms
            apikey = xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

    [[emonCMS_remote]]
        Type = EmonHubEmoncmsReporter
        [[[init_settings]]]
        [[[runtimesettings]]]
            url = http://emoncms.org
            apikey = xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx


## 4.) Things to do (Optional but recommended) 

    $ rpi-rw

Change the default raspberrypi ssh password

   $ passwd
    
Change the admin raspberrypi password

   $ sudo passwd
    
Enter current password and then the new password of your choice.
    
Always remember to put the OS partiion back into read-only mode. This will extend the lifespan of your SD Card.

### Monitoring and Debugging

To view logfile entries:

    $ tail -f /var/log/emonhub/emonhub.log
    $ tail -f /var/log/feedwriter.log
    $ tail -f /var/log/emoncms.log
    
To stop / start the emonHub service 

	$ sudo service emonhub start/stop/status

Monitor disk load with sysstat:

    $ sudo iostat 60 (will give you 1 minuite disk load average, note kb_wrtn/s value)

kb_wrtn/s should be around 0.5-1.5 kb_wrtn/s
