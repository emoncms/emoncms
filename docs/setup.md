## Using the SD card


### 1) Posting to Emoncms.org

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

### 2) Recording data locally and/or posting to emoncms.org

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

In the Dispatchers section enter the write apikey of your local emoncms account and in the Listeners section set the group and frequency of your rfm12pi adapter board and rf network.

Save and exit nano text editor using [Ctrl + X] then [Y] and [Enter]

Set the raspberrypi os back into read-only mode.

    $ rpi-ro

That's it, if you have sensor nodes sending data, inputs should start appearing in the inputs section your emoncms account in a few seconds.

Return to the OpenEnergyMonitor Guide to setup your sensor nodes and map the inputs to create feeds and build your dashboard in emoncms: http://openenergymonitor.org/emon/guide


### Things to do (Optional but recomended) 

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

Monitor disk load with sysstat:

    $ sudo iostat 60 (will give you 1 minuite disk load average, note kb_wrtn/s value)

kb_wrtn/s should be around 0.5-1.5 kb_wrtn/s
