## Pre-build SD Card setup

Insert SD card and power up. It usually takes a minute to boot up the ACT light on the PI should be actively flickering for the first minute.

Find the raspberrypi's IP address on your network, this can usually be found in your router status page. Alternatively there's a useful app called fing that can be used to scan for devices on a network.

Login to the raspberrypi with SSH (Putty is a useful tool to do this on windows).

    user@user:~$ ssh pi@192.168.1.70
    pi@192.168.1.70's password:

The password is: raspberry

### Enabling and logging to the local installation of emoncms

The local installation of emoncms is disabled as default. To enable it first put the os partition into write mode with

    rpi-rw
    
and then run:
    
    localemoncms-enable

Note: to disable again: localemoncms-disable

In your internet browser window, enter the ip address of the raspberrypi. This should bring up the emoncms login page. 

Login with the default admin account name **raspberry** and password **raspberry**.

Its recommended that you change the default admin account name, password and email once you login.

The emoncms installation is ready to use and can be used much in the same way as an account on emoncms.org.

Also installed on the SD card is emonhub. Emonhub can be used to receive serial data from an attached rfm12pi adapter board and forward that data to the local emoncms install or/and forward the data to a remote emoncms account.

To configure emonhub to post to the local installation of emoncms note down the write apikey that is displayed on the account page.

Open the emonhub config file for editing:
    
    nano /boot/emonhub.conf

In the Dispatchers section enter the write apikey of your local emoncms account and in the Listeners section set the group and frequency of your rfm12pi adapter board and rf network.

Save and exit.

Set the raspberrypi os back into read-only mode.

    rpi-ro

That's all that is needed, data should now appear on the inputs page of the local emoncms account.

### Things to do

    rpi-rw

Change the default raspberrypi ssh password

    passwd
    
Change the admin raspberrypi password

    sudo passwd
    
Enter current password and then the new password of your choice.
    
Always remember to put the OS partiion back into read-only mode. This will extend the lifespan of your SD Card.

### Monitoring and Debugging

To view logfile entries:

    tail -f /var/log/emonhub/emonhub.log
    tail -f /var/log/feedwriter.log
    tail -f /var/log/emoncms.log

Monitor disk load with sysstat:

    sudo iostat 60 (will give you 1 minuite disk load average, note kb_wrtn/s value)

kb_wrtn/s should be around 0.5-1.5 kb_wrtn/s
