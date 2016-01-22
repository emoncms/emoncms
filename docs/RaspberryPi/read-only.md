# Enable root filesystem read-only mode

## Setup Data partition

Assuming creating 300Mb data partition and starting with SD card image expanded to fill SD card (4GB in this example).

# Reduce size of root partition using Gparted

Using Gparted on Ubuntu reduce size of root partition by 300Mb to make space for data partition. Reccomend leaving 10Mb free space at end of SD card 



# Creating 3rd partition:

    sudo fdisk -l
    Note end of last partition (assume 7391231)
    sudo fdisk /dev/mmcblk0
    enter: n->p->3
    enter: 7391232
    enter: default or 7821312
    enter: w (write partition to disk)
    fails with error, will write at reboot
    sudo reboot
    
    On reboot, login and run:
    sudo mkfs.ext2 -b 1024 /dev/mmcblk0p3
    
**Note:** *We create here an ext2 filesystem with a blocksize of 1024 bytes instead of the default 4096 bytes. A lower block size results in significant write load reduction when using an application like emoncms that only makes small but frequent and across many files updates to disk. Ext2 is chosen because it supports multiple linux user ownership options which are needed for the mysql data folder. Ext2 is non-journaling which reduces the write load a little although it may make data recovery harder vs Ext4, The data disk size is small however and the downtime from running fsck is perhaps less critical.*
    
    
Create a directory that will be a mount point for the rw data partition

    mkdir /home/pi/data
    
## Read-only mode

Then run these commands to make changes to filesystem

    sudo cp /etc/default/rcS /etc/default/rcS.orig
    sudo sh -c "echo 'RAMTMP=yes' >> /etc/default/rcS"
    sudo mv /etc/fstab /etc/fstab.orig
    sudo ln -s /home/pi/emonpi/fstab /etc/fstab
	sudo chmod a+x /etc/fstab
    sudo mv /etc/mtab /etc/mtab.orig
    sudo ln -s /proc/self/mounts /etc/mtab
    
The Pi will now run in Read-Only mode from the next restart. Before restarting create two scripts to switch between read-only and write access modes. These scripts are in the emonPi git repo and can be installed with:

Firstly “ rpi-rw “ will be the command to unlock the filesystem for editing, and "rpi-ro" will put the system back to read-only mode:

    sudo ln -s /home/pi/emonpi/rpi-ro /usr/bin/rpi-ro
    sudo ln -s /home/pi/emonpi/rpi-rw /usr/bin/rpi-rw
        
Lastly reboot for changes to take effect

    sudo shutdown -r now
    
Login again, change data partition permissions:

    sudo chmod -R a+w data
    sudo chown -R pi data
    sudo chgrp -R pi data

Add message at shell login to alert users to RO mode:

	sudo nano /etc/motd

Add the line:

	The file system is in Read Only (RO) mode. If you need to make changes, use the command rpi-rw to put the file system in Read Write (RW) mode. Use rpi-ro to return to RO mode. The /home/pi/data directory is always in RW mode 