##Move Operating System (Root) to External HDD
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually move the operating system partition (root) to an USB HDD or to lower the write frequency to the SD card by using the low-write mode.
####Preparation
Before following this guide, it is essential that you have a git installed, working version of emoncms installed on your Raspberry Pi.  
Update emoncms to current version

    cd /var/www/emoncms && git pull
    
Attach your USB hard disk drive to your Raspberry Pi ensuring that the drive's power requirements are met. This will usually mean attaching the drive via a powered USB Hub, or using a self-powered drive, as powering the drive from the Raspberry Pi's USB port almost certainly will not work  
Find & note the device name of your attached drive

    sudo fdisk -l

If you only have one USB drive attached, it will most likely be `sda`
####Running the shell script
Make the script executable

    sudo chmod +x /var/www/emoncms/scripts/usb_hdd/usb_hdd

Assuming that your attached drive is `sda`, but if not, substitute `sda` with the correct device name

**It's important that you have the correct device name, as the following script WILL re-format that drive, wiping it of all data!**

    sudo /var/www/emoncms/scripts/usb_hdd/./usb_hdd -d /dev/sda

The script will ask for confirmation twice, and then;
+ Create a filesystem on the new drive
+ Copy your operating system from your SD Card to your new drive
+ Edit cmdline.txt to run the operating system from your new drive
+ Stop fstab from mounting the SD card root partition

..which will take a long time - possibly 20 minutes or more, so **please be patient!**
After a reboot, you can check if your operating system is now running from your USB HDD

    readlink /dev/root

####Problems?    
Your Raspberry Pi should now be running from your attached drive, but if you do encounter problems, the script has created a backup of your original /boot/cmdline.txt file, which can be restored by editing the first partition of your SD card on another computer (Windows or OS X work fine).  
The 'root' partition of your SD card still remains unchanged.  
Additional support is also available in the OEM forum giving as much detail as possible.
