## Backing up a raspberrypi or emoncms.org account

### Backing up an emoncms.org account (or other remote server account)

**(Optional) To view data on the backup computer a local emoncms installation is required**

Create an account on the backup emoncms installation and note down the mysql credentials.

1) Download the usefulscripts repository: [https://github.com/emoncms/usefulscripts](https://github.com/emoncms/usefulscripts)

2) Open Backup/backup.php in a text editor. 

- Set $remote_server and $remote_apikey to correspond to the remote emoncms account you wish to download from.
- Set $link\_to\_local\_emoncms to true if you wish to access your data within a local installation of emoncms. Set $local\_emoncms\_location and $local\_emoncms\_userid to link to your local emoncms installation.
- Set $link\_to\_local\_emoncms to false if you just want to download the data without linking to a local emoncms install (non-mysql data only). Set $dir to the directory on your computer you wish to download the data. Manually create the folders: phpfina, phpfiwa, phptimeseries, phptimestore within this folder.

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

    /var/lib/phpfiwa
    /var/lib/phpfina
    /var/lib/phptimeseries
    /var/lib/timestore
    
**Important** Make sure you disable oem\_gateway/emonhub or raspberrypi\_run and any posting to the http api's (stop apache) before copying the data files so that when you make the copy the data is in a state where its not being written to.

Make a copy of the emoncms application folder usually found under /var/www/emoncms
