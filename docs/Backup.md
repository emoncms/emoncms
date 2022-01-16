## Backing up, archiving, or cloning an emoncms account:

There are a number of different approaches to backing up an emoncms account. We would recommend option 1 or 2 in most cases.

### 1\. Download account data to a local raspberrypi using the sync module

*Suitable for backing up an emoncms.org account or other remote server.*

This is a nice solution as it provides a local installation of emoncms that you can use to explore your backed up or archived data, you can also transfer to using this installation directly rather than posting data to emoncms.org or an other remote server. It also avoids any complicated installation process on your computer, you just need a RaspberryPi and an SD card running the pre-built emonSD image. The SD card image can be downloaded here: https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log

Once you have the RaspberryPi up and running, create an emoncms account on the emoncms installation running on the Pi and then navigate to Setup > Sync. Enter the login credentials of your emoncms.org account and click on Download All to download all of your emoncms feed data. Once complete navigate to Setup > Feeds to start browsing the downloaded feed data. For more information please see: https://github.com/emoncms/sync/

### 2\. Download account data to your computer using the emoncms backup_py python script

*Suitable for backing up an emoncms.org account, other remote server or local raspberrypi installation*

This is a nice cross platform solution for backing up to your computer. You just need to have python installed and a couple of associated libraries. See the forum post here for details on how to use this tool: https://community.openenergymonitor.org/t/python-based-emoncms-backup-utility/19526. This tool also includes an option to covert the downloaded binary data into CSV format.

### 3\. Use the emoncms backup module to create a backup archive

*Suitable for backing up your own remote server or local raspberrypi installation*

Designed for those running emoncms on a RaspberryPi running the standard emonSD image, this option creates a compressed backup archive of all local emoncms account data. The archive can then be used to restore a system in case of failure. To use this tool login to your local emoncms account and navigate to Setup > Backup. For more information see: https://guide.openenergymonitor.org/setup/import/


### 4\. Download account data to your computer using the emoncms backup php script

*Suitable for backing up an emoncms.org account, other remote server or local raspberrypi installation*

This script is very similar to the python script in option 2. It does provides an additional option to download all account data and then link to a local emoncms installation that you may have running on your computer. 

If you wish to link to a local emoncms installation, create an account first on the local installation.

1) Download the usefulscripts repository: [https://github.com/emoncms/usefulscripts](https://github.com/emoncms/usefulscripts)

2) Open Backup/backup.php in a text editor. 

- Set $remote_server and $remote_apikey to correspond to the remote emoncms account you wish to download from.
- Set $link\_to\_local\_emoncms to true if you wish to access your data within a local installation of emoncms. Set $local\_emoncms\_location and $local\_emoncms\_userid to link to your local emoncms installation.
- Set $link\_to\_local\_emoncms to false if you just want to download the data without linking to a local emoncms install (non-mysql data only). Set $dir to the directory on your computer you wish to download the data. Manually create the folders: phpfina, phptimeseries, phptimestore within this folder.

3) Run the backup script from terminal with:

    php backup.php

Tested with emoncms.org (v8.0.9: 4 July 2014), and local emoncms v8.2.8

That's it, it should now work through all your feeds. When you first run this script it can take a long time. When you run this script again it will only download the most recent data and so will complete much faster.

### 5\. Backing up a full emoncms installation, all accounts, full SQL database (raspberrypi install or your own server)

*Suitable for backing up your own remote server or local raspberrypi installation*

Start by making a backup of your emoncms data and emoncms application folder.

To export a backup of your emoncms mysql data: 

    mysqldump -u root -p emoncms > emoncms_backup.sql
    
Or if you have a lot of feed data stored in mysql, you can export the meta data only with:
    
    mysqldump -u root -p emoncms users input feeds dashboard multigraph > emoncms_backup.sql
    
You can make a direct directory copy of the /var/lib/mysql/emoncms folder if the mysql dump is too large.

Make a backup copy of the feed data folders on your system, the default locations on linux are:

    /var/lib/phpfina
    /var/lib/phptimeseries
    
**Important** Make sure you stop apache2, emonhub, emoncms_mqtt and the feedwriter services before copying the data files so that when you make the copy the data is in a state where its not being written to.

Make a copy of the emoncms application folder usually found under /var/www/emoncms
