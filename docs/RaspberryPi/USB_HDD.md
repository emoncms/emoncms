##Move Operating System (Root) to External HDD
Due to the number of writes that the full version of emoncms makes, the lifespan of an SD card will almost certainly be shortened, and it is therefore recommended that you eventually move the operating system partition (root) to an USB HDD or to lower the write frequency to the SD card by using the [low-write mode](Low-write-mode.md).
####Preparation
Ensure that your Raspbian operating system boots into command console, and not into the desktop environment. This is because the desktop will automatically mount any attached USB drive, and prevent the script's operation.  
Open the Raspbian configuration tool:

`sudo raspi-config`

Select 'Boot Options' (this may be worded slightly different depending upon the system version) and set 'Text Console' as your prefered boot option.  
Select 'OK' & 'Finish', and when prompted re-boot your system.

It is essential that emoncms was initially installed by following the [Raspberry Pi installation guide](readme.md) or you have used git to install a working version of emoncms on your Raspberry Pi.  
Update emoncms to the current version:

    cd /var/www/emoncms && git pull
    
Attach your USB hard disk drive to your Raspberry Pi ensuring that the drive's power requirements are met. This will usually mean attaching the drive via a powered USB Hub, or using a self-powered drive, as powering the drive from the Raspberry Pi's USB port almost certainly will not work.  
Find & note the device name of your attached drive:

    sudo fdisk -l

If you only have one USB drive attached, it will most likely be `sda`
####Running the shell script
Make the script executable:

    sudo chmod +x /var/www/emoncms/scripts/usb_hdd/usb_hdd

Assuming that your attached drive is `sda`, but if not, substitute `sda` in the following command with the correct device name:

**It's important that you have the correct device name, as the following script WILL re-format that drive, wiping it of all data!**

    sudo /var/www/emoncms/scripts/usb_hdd/./usb_hdd -d /dev/sda

The script will ask for confirmation twice, and then;
+ Create a filesystem on the new drive
+ Copy your operating system from your SD Card to your new drive
+ Edit cmdline.txt to run the operating system from your new drive
+ Stop fstab from mounting the SD card root partition

..which will take a long time - possibly 20 minutes or more, so **please be patient!**  
After a reboot, you can check if your 'root' operating system is now running from your USB HDD:

    ls -l /dev/disk/by-label

####Problems?    
Your Raspberry Pi should now be running from your attached drive, but if you do encounter problems, the script has created a backup of your original /boot/cmdline.txt file, which can be restored by editing the first partition of your SD card on another computer (Windows or OS X work fine).  
The 'root' partition of your SD card still remains unchanged.  
Additional support is also available in the OEM forum giving as much detail as possible.
