## HDD Setup

The emonSD-21-7-14.img.zip image can be used with a harddrive or an SD Card. 

1. Write the emonSD-21-7-14.img on to both your harddrive and an SD Card.

### Change /boot/cmdline.txt on the boot partition of the SD card

Paul from the OpenEnergyMonitor forums has written an alternative guide for setting up the Pi with file system on a hard drive which should be easier for those running Windows, see forum post:

[http://openenergymonitor.org/emon/node/5092] (http://openenergymonitor.org/emon/node/5092)

Mount the SD Card on your computer and open the boot) partition. Open to edit the file:

/boot/cmdline.txt

Over write with:

dwc_otg.lpm_enable=0 console=tty1 root=/dev/sda2 rootfstype=ext4 elevator=deadline rootwait

This both tells the PI that the root filesystem is on /dev/sda2 and to not use the serial port for raspberrypi debug purposes as we need it for emoncms.

### Edit /etc/fstab on the HDD

Mount the Harddrive on your computer and open the main partition. Open to edit the file:

 /etc/fstab

Change the root device from: /dev/mmcblk0p2 / to be /dev/sda2

That's all that is needed to get your raspberrypi running of a harddrive rather than the SD card. You can now insert the SD card in the raspberrypi and connect up the harddrive, power up and use the raspberrypi in the same way as you would use the pi if it was running off an SD card.
