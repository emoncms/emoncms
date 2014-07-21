## Pre-build SD Card setup

Insert SD card and power up. It usually takes a minute to boot up the ACT light on the PI should be actively flickering for the first minute.

Find the raspberrypi's IP address on your network, this can usually be found in your router status page. Alternatively there's a useful app called fing that can be used to scan for devices on a network.

Login to the raspberrypi with SSH (Putty is a useful tool to do this on windows).

    user@user:~$ ssh pi@192.168.1.70
    pi@192.168.1.70's password:

The password is: raspberry

### Logging to the local installation of emoncms

In your internet browser window, enter the ip address of the raspberrypi. This should bring up the emoncms login page. There are no accounts created yet so you will need to start by registering an account. The emoncms installation is ready to use and can be used much in the same way as an account on emoncms.org.

Also installed on the SD card is emonhub. Emonhub can be used to receive serial data from an attached rfm12pi adapter board and forward that data to the local emoncms install or/and forward the data to a remote emoncms account.

To configure emonhub to post to the local installation of emoncms note down the write apikey that is displayed on the account page.

In the ssh window, set the raspberrypi os into write mode with:

    rpi-rw

Open the emonhub config file for editing:
    
    nano /etc/emonhub/emonhub.conf

In the Dispatchers section enter the write apikey of your local emoncms account and in the Listeners section set the group and frequency of your rfm12pi adapter board and rf network.

Save and exit.

Set the raspberrypi os back into read-only mode.

    rpi-ro

That's all that is needed, data should now appear on the inputs page of the local emoncms account.

### Things to do

    rpi-rw

Change the default raspberrypi ssh password

    passwd
    
Enter current password and then the new password of your choice.

Turn off ability to create further accounts in emoncms:

    nano /var/www/emoncms/settings.php
    
At the bottom set:

    $allowusersregister = false;

and to speed emoncms up a bit set:

    $dbtest = false;
    
Change the logging level on emonhub to WARNING instead of DEBUG

    nano /etc/emonhub/emonhub.conf

Near the top of the file, change loglevel to:

    loglevel = WARNING
    
Always remember to put the OS partiion back into read-only mode. This will extend the lifespan of your SD Card.
