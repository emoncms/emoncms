## HDD Setup

The emonSD-21-7-14.img.zip image can be used with a harddrive or an SD Card. Start by writing the emonSD-21-7-14.img on to both your harddrive and an SD Card. The SD card is needed for initial boot up, after it boots it will use the harddrive.

### Change /boot/cmdline.txt on the boot partition of the SD card

Paul ([pb66](http://openenergymonitor.org/emon/user/4440)) from the OpenEnergyMonitor forums has written an alternative guide for setting up the Pi with file system on a hard drive which should be easier for those running Windows, see forum post: [http://openenergymonitor.org/emon/node/5092] (http://openenergymonitor.org/emon/node/5092)

Mount the SD Card on your computer and open the boot) partition. Open to edit the file:

    /boot/cmdline.txt

Over write with:

    dwc_otg.lpm_enable=0 console=tty1 root=/dev/sda2 rootfstype=ext4 elevator=deadline rootwait

This both tells the PI that the root filesystem is on /dev/sda2 and to not use the serial port for raspberrypi debug purposes as we need it for emoncms.

### Edit /etc/fstab on the HDD

Mount the Harddrive on your computer and open the main partition. Open to edit the file /etc/fstab:
 
    tmpfs           /tmp            tmpfs   nodev,nosuid,size=30M,mode=1777       0    0
    tmpfs           /var/log        tmpfs   nodev,nosuid,size=30M,mode=1777       0    0
    proc            /proc           proc    defaults                              0    0
    /dev/mmcblk0p1  /boot           vfat    defaults                              0    2
    /dev/mmcblk0p2  /               ext4    defaults,ro,noatime,errors=remount-ro 0    1
    /dev/mmcblk0p3  /home/pi/data   ext2    defaults,rw,noatime                   0    2

Change the root device "/" from: /dev/mmcblk0p2 to be /dev/sda2
Change the "/home/pi/data" device from: /dev/mmcblk0p3 to be /dev/sda3

    tmpfs           /tmp            tmpfs   nodev,nosuid,size=30M,mode=1777       0    0
    tmpfs           /var/log        tmpfs   nodev,nosuid,size=30M,mode=1777       0    0
    proc            /proc           proc    defaults                              0    0
    /dev/mmcblk0p1  /boot           vfat    defaults                              0    2
    /dev/sda2       /               ext4    defaults,ro,noatime,errors=remount-ro 0    1
    /dev/sda3       /home/pi/data   ext2    defaults,rw,noatime                   0    2

**Note:** To make the OS partition load in write mode change:

    /dev/sda2       /               ext4    defaults,ro,noatime,errors=remount-ro 0    1

to:

    /dev/sda2       /               ext4    defaults,rw,noatime,errors=remount-ro 0    1

That's all that is needed to get the data partition of the raspberrypi running of a harddrive rather than the SD card. You can now insert the SD card in the raspberrypi and connect up the harddrive, power up and use the raspberrypi in the same way as you would use the pi if it was running off an SD card.

The data partition on the harddrive will still be around 900Mb, you may wish to extend the partition, there are plenty of good guides available on the internet for this search for extending an ext2 filesystem.
