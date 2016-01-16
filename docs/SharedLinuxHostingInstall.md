# Shared Linux Hosting

Your shared hosting provider should already have a LAMP server installed. You may need to ask your hosting provider to enable mod_rewrite. It's unlikely that redis will be available (redis is used to improve performance through caching), but emoncms can be run without it.

To install emoncms on a shared server

1) Check with your hosting provider that mod_rewrite is enabled

2) Download the emoncms zip file from:

**You may want to install one of the other branches of emoncms here, perhaps to try out a new feature set not yet available in the master branch. See the branch list and descriptions on the [start page](https://github.com/emoncms/emoncms)**

[https://github.com/emoncms/emoncms/archive/stable.zip](https://github.com/emoncms/emoncms/archive/stable.zip)

Unzip to your shared server's public_html folder, rename the folder to emoncms.
i.e. You should end up with all the files in the directory public_html/emoncms/

3) Create a mysql database for your emoncms installation, note down its name, username and password.

4) In your shared hosting /home/username folder create a folder called emoncmsdata to hold your emoncms feed data.  
(Note: NOT public_html as the data files should not be publicly accessible).  
Then create three folders within your emoncmsdata folder called: phpfiwa, phpfina and phptimeseries

5) In the emoncms app directory make a copy of default_settings.php and call it settings.php.  
Open settings.php and enter your mysql username, password and database.  
In the feed_settings section uncomment the datadir definitions and set them to the location of each of the feed engine data folders on your system.   
In the 'Other settings' section, change the $log_filename location to:  
```$log_filename = dirname(__FILE__).'/' . 'emoncms.log';```


6) That's it, emoncms should now be ready to use!
