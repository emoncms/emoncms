# Backing up an emoncms.org account (or other remote server account)

There are three main approaches to backing up an emoncms.org account

## 1\. Download account data to a local raspberrypi using the sync module

This is a nice solution as it provides a local installation of emoncms that you can use to explore your backed up or archived data. It also avoids any complicated installation process on your computer, you just need a RaspberryPi and an SD card running the pre-built emonSD image. The SD card image can be downloaded here: https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log

Once you have the RaspberryPi up and running, create an emoncms account on the emoncms installation running on the Pi and then navigate to Setup > Sync. Enter the login credentials of your emoncms.org account and click on Download All to download all of your emoncms feed data. Once complete navigate to Setup > Feeds to start browsing the downloaded feed data.

## 2\. Download account data to your computer using a python script

This is a nice cross platform solution for backing up to your computer. You just need to have python installed and a couple of associated libraries. See the forum post here for details on how to use this tool: https://community.openenergymonitor.org/t/python-based-emoncms-backup-utility/19526. This tool also includes an option to covert the downloaded binary data into CSV format.

## 3\. Download account data to your computer using the emoncms backup php script

This script is very similar to the python script in option 2. It does provides an additional option to download all account data and then link to a local emoncms installation that you may have running on your computer. 

---

## Backing up a raspberrypi or emoncms.org account

### Backing up an emoncms.org account (or other remote server account)

**(Optional) To view data on the backup computer a local emoncms installation is required**

Create an account on the backup emoncms installation and note down the mysql credentials.

1) Download the usefulscripts repository: [https://github.com/emoncms/usefulscripts](https://github.com/emoncms/usefulscripts)

2) Open Backup/backup.php in a text editor. 

- Set $remote_server and $remote_apikey to correspond to the remote emoncms account you wish to download from.
- Set $link\_to\_local\_emoncms to true if you wish to access your data within a local installation of emoncms. Set $local\_emoncms\_location and $local\_emoncms\_userid to link to your local emoncms installation.
- Set $link\_to\_local\_emoncms to false if you just want to download the data without linking to a local emoncms install (non-mysql data only). Set $dir to the directory on your computer you wish to download the data. Manually create the folders: phpfina, phptimeseries, phptimestore within this folder.

3) Run the backup script from terminal with:

    php backup.php

Tested with emoncms.org (v8.0.9: 4 July 2014), and local emoncms v8.2.8

That's it, it should now work through all your feeds. When you first run this script it can take a long time. When you run this script again it will only download the most recent data and so will complete much faster.

### Backing up a full emoncms installation (raspberrypi install or your own server)

Start by making a backup of your emoncms data and emoncms application folder.

To export a backup of your emoncms mysql data: 

    mysqldump -u root -p emoncms > emoncms_backup.sql
    
Or if you have a lot of feed data stored in mysql, you can export the meta data only with:
    
    mysqldump -u root -p emoncms users input feeds dashboard multigraph > emoncms_backup.sql
    
You can make a direct directory copy of the /var/lib/mysql/emoncms folder if the mysql dump is too large.

Make a backup copy of the feed data folders on your system, the default locations on linux are:

    /var/lib/phpfina
    /var/lib/phptimeseries
    /var/lib/timestore
    
**Important** Make sure you disable oem\_gateway/emonhub or raspberrypi\_run and any posting to the http api's (stop apache) before copying the data files so that when you make the copy the data is in a state where its not being written to.

Make a copy of the emoncms application folder usually found under /var/www/emoncms
