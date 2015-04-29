## Upgrading

### 1) Backup

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

Make a copy of the emoncms application folder usually found under /var/www/emoncms

### 2) Download the latest version of emoncms into a new folder

To test the new version without overwritting your current installation git clone emoncms into new folder with a different name to your exisiting emoncms installation:

    cd /var/www
    git clone https://github.com/emoncms/emoncms.git emoncms_new 

### 3) Create the settings.php file

Create a **new** settings.php file from the new installations default.settings.php file.

Open settings.php in an editor:

    $ nano settings.php

Enter in your database settings.

    $username = "USERNAME";
    $password = "PASSWORD";
    $server   = "localhost";
    $database = "emoncms";

## 4) Upgrading to v8 from older versions

Create data repositories for emoncms feed engine's:

    sudo mkdir /var/lib/phpfiwa
    sudo mkdir /var/lib/phpfina
    
    sudo mkdir /var/lib/phptimeseries (if you dont already have phptimeseries)

    sudo chown www-data:root /var/lib/phpfiwa
    sudo chown www-data:root /var/lib/phpfina
    sudo chown www-data:root /var/lib/phptimeseries
    
### 5) Update database

Make sure you have a backup of your emoncms meta data before updating the database this can be done by using mysqldump:

    mysqldump -u root -p emoncms users input feeds dashboard multigraph > emoncms_backup.sql

Log in with the administrator account (first account created)

Click on the *Admin* tab (top-right) and run database update.

If you cant login, use the authentication bypass to run the updater:

Add the following line to the bottom of *settings.php* to enable a special database update only session, be sure to remove this line from settings.php once complete:

    $updatelogin = true;
    
In your internet browser open the admin/view page and click on the database update and check button to launch the database update script.

    http://localhost/emoncms/admin/view
    
You should now see a list of changes to be performed on your existing emoncms database.

Click on apply changes to apply these changes.

### 6) Confirm that you can access your data via the new emoncms installation

If you cannot see any of your data in the new installation:

1) try clearing your browser cache
2) if you already have redis installed, try reseting redis with:
        
    $ redis-cli and then: flushall

3) Make a note of any errors that you see, check if there are any errors in your browser's console window. Check that data is still coming in and viewable in your old emoncms installation. Post on the forums with information on the errors that you see and we can try to help.

### 7) Rename your old emoncms folder to emoncms_old and rename emoncms_new to emoncms

This should complete the upgrade. If your data stops getting inserted try posting data manually and noting any errors that appear:

    http://localhost/emoncms/input/post.json?csv=100,200,300

Post on the forums with information on the errors that you see and we can try to help.

In the mean time you may want to switch back to your old emoncms application folder.

### Improve performance with Redis

As of version 7 & 8 of emoncms, redis can be used to improve performance, using redis is optional but highly recommended.

To upgrade you will need redis server installed and the phpredis client:

    sudo apt-get install redis-server
    sudo pecl install redis
    
Add pecl redis module to php5 config
    
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
    sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'

### Remove raspberrypi module cron entry

Open crontab:
    
    sudo nano /etc/crontab 

if there is an entry for raspberrypi_run.php it needs to be removed in order to use the newer deamon approach.

### Clear browser cache

You may need to clear your browser cache if an interface appears buggy.
