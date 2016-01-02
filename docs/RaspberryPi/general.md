###Updating emoncms via Git
Emoncms is regularly updated to add new functions and general improvements, and updating your emoncms installation can be achieved quickly and easily using Git.

It is however important, that emoncms was initially installed by following the [Raspberry Pi installation guide](readme.md) or by git-cloning the emoncms github repository.

To check if an emoncms update is available, and update your installation:

`cd /var/www/emoncms && git pull`

The command 'git pull' will compare your installed version of emoncms with the emoncms software stored in Github, and if any new files are available, they will be automatically downloaded to your installation.

Carefully note the message displayed after running the command, as it will tell you if the update was sucessful, and also list which files were updated in the process. With the majority of updates, nothing more needs to be done and your installation will now be up to date, however if you see that the default configuration file - 'default.settings.php' has been updated, it will be necessary to update the settings.php file as follows.

***NOTE*** _The following is only necessary if the default.settings.php has been updated!_

Backup your settings file:

`cd /var/www/emoncms/ && mv settings.php oldsettings.php`

Copy the new updated default.settings.php as settings.php

`cp default.settings.php settings.php`

Open settings.php in an editor:

`nano settings.php`

Update your settings to use your Database 'user' & 'password', which will enable emoncms to access the database:  
This is exactly the same process which was done during the [initial emoncms installation](readme.md#configure-emoncms-database-settings).  
If you cannot recall your Database 'user' & 'password', you can obtain them from the 'oldsettings.php' backup file.

    $server   = "localhost";
    $database = "emoncms";
    $username = "emoncms";
    $password = "new_secure_password";

Save and exit.

***

###Module installation

[Apps Module](general.md#install-emoncms-apps-module)  
[Device Module](general.md#install-emoncms-device-module)  
[Dashboard Module](general.md#install-emoncms-dashboard-module)

####Install emoncms Apps Module
Installing 'Apps' to emoncms adds a number of pre-formatted templates, enabling data to be displayed across a wide range of devices such as desktops, tablets and smartphones.

To install Apps, cd to the Modules folder, and git clone 'apps':

`cd /var/www/emoncms/Modules && git clone https://github.com/emoncms/app.git`

The 'apps' need to save their configurations in the emoncms database, so in your browser - update your emoncms database: Setup > Administration > Update database (you may need to log out, and log back into emoncms to see the Administration menu).

#####App configuration
You should now see a new menu item - 'Apps' on the menu bar, with a number of sub-menus. At this stage they will not display any data as they have not been configured.  
To configure each app, using 'My Electric' as an example, select the spanner/wrench icon top right which will open the 'My Electric' configuration options page, and select the appropriate feeds & currency details and 'save'.  
The choice of feeds is totally a user preference, and you could use appropriate feeds to:
* Display Power imported
* Display Power exported
* Solar Generation
* etc!

#####App update
The emoncms 'apps' are updated to add new functions and general improvements, and updating your apps installation can be achieved quickly and easily using Github.

To check if an 'apps' update is available, and update your installation:

`cd /var/www/emoncms/Modules/app && git pull`

The command 'git pull' will compare your installed version of 'Apps' with the 'Apps' software stored in Github, and if any new files are available, they will be automatically downloaded to your installation.

Carefully note the message displayed after running the command, as it will tell you if the update was successful or not.

####Install emoncms Device Module
The device setup will allow the creation of inputs and feeds automatically from a device template, and use a devicekey per device that is user configured, instead of an apikey.

To install 'device', cd to the Modules folder, and git clone 'device':

`cd /var/www/emoncms/Modules && git clone https://github.com/emoncms/device.git`

The 'device' module needs to save it's configurations in the emoncms database, so in your browser - update your emoncms database: Setup > Administration > Update database (you may need to log out, and log back into emoncms to see the Administration menu).

####Install emoncms Dashboard Module
The dashboard module enables users to create customisable workspaces, by dragging and dropping widgets, visualisations and other custom objects.

`cd /var/www/emoncms/Modules && git clone https://github.com/emoncms/dashboard.git`

The 'dashboard' module needs to save it's configurations in the emoncms database, so in your browser - update your emoncms database: Setup > Administration > Update database (you may need to log out, and log back into emoncms to see the Administration menu).

***
