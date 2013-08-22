# Transitionary emoncms: mysql to timestore
This branch of emoncms is to be used when converting from an existing installation with mysql data feeds over to timestore based storage.

Timestore is time-series database designed specifically for time-series data developed by Mike Stirling.

[mikestirling.co.uk/redmine/projects/timestore](mikestirling.co.uk/redmine/projects/timestore)

**Faster Query speeds**
With timestore feed data query requests are about 10x faster (2700ms using mysql vs 210ms using timestore).
*Note:* initial benchmarks show timestore request time to be around 45ms need to investigate the slightly slower performance may be on the emoncms end rather than timestore.

**Reduced Disk use**
Disk use is also much smaller, A test feed stored in an indexed mysql table used 170mb, stored using timestore which does not need an index and is based on a fixed time interval the same feed used 42mb of disk space. 

**In-built averaging**
Timestore also has an additional benefit of using averaged layers which ensures that requested data is representative of the window of time each datapoint covers.

## 1) Download, make and start timestore

    cd /home/pi
    git clone https://github.com/mikestir/timestore.git
    cd timestore
    make
    sudo mkdir /var/lib/timestore
    cd /home/pi/timestore/src
    sudo ./timestore

The last line there starts timestore as a deamon, you can get the process id by typing

    pidof timestore

and then stop it with:

    kill PID-OF-TIMESTORE

Alternatively timestore can be run in a terminal session by calling:

    sudo ./timestore -d

## 2) Download Transitionary Timestore emoncms branch

The transitionary branch keeps a lot of things like input processing and visualisations the same, just allowing for swapping out mysql for timestore for the realtime feed datatype only. It use both types of feed storage in parallel. This makes it easy to inspect the feed before conversion to work out the best interval rate to convert the feed too and then once the conversion is complete emoncms will automatically use the timestore feed data instead of the mysql data. There is also an interface for selecting the timestore interval to convert the feed to.

In the /var/www/ folder:

    cd /var/www

First rename your current emoncms installation to something like emoncms_tmp so that you have a backup copy of your current install.

Download the transitionary version, run:

    git clone -b emoncmsorg https://github.com/emoncms/emoncms.git

Create settings.php from default.settings.php

    cp /var/www/emoncms/default.settings.php /var/www/emoncms/settings.php

Enter your mysql database settings in settings.php

Launch emoncms in your browser:

    http://IP-ADDRESS/emoncms

Log in with the administrator account (first account created)

Click on the *Admin* tab (top-right) and run database update.

Click on feeds, check that everything is working as expected, if your monitoring equipment is still posting you should see data coming in as usual. 

**Note:** You will notice at this point that there are two new fields *interval* and *Size*. You can populate these helper values by using a script found in the github repo emoncms/usefulscripts (further details below for conversion step), run the script usefulscripts/feedsize/feedstats.php from terminal, enter your mysql settings in the script before running it. 

Click on the link to the conversion page. Follow the guide to select timestore interval rates for each of your realtime feeds. 

Once complete click submit.

## 3) Run conversion script 

Download the emoncms *usefulscripts* folder, its best to place this in a non-web accessible directory rather than /var/www.

    git clone https://github.com/emoncms/usefulscripts.git

Open the export script located in *usefulscripts/convert\_to\_timestore/export.php* and enter in your mysql username, password and database name. Enter also your timestore admin password. The timestore admin password can be found by opening the password file by calling:

    nano /var/lib/timestore/adminkey.txt

Start the conversion! run export.php from the command line using:

    php export.php

Once complete, check your emoncms feeds, historical data should now load much faster and data should still be coming in as normal.

Your mysql feed data is still intact, before deleting it make sure your happy with the conversion interval of your newly converted timestore feeds.

See timestore forum discussion: http://openenergymonitor.org/emon/node/2651

