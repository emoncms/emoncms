# Emoncms v5.0

See main site: http://emoncms.org

Emoncms is an open source energy visualisation web application, the main feature's include

## Input processing
Input processing allows for conversion and processing before storage, there are over 15 different input processes from simple calibration to power to kWh-per-day data, or histogram data.

## Visualisation
Zoom through large datasets with flot and ajax powered level-of-detail amazing super powered graphs!

## Visual dashboard editor
Create dashboards out of a series of widgets and visualisations with a fully visual drag and drop dashboard editor.

## Open Source
We believe open source is a better way of doing things and that our cloud based web applications should also be open source.

Emoncms is part of the OpenEnergyMonitor project. A project to build a fully open source hardware and software energy monitoring platform.

With Emoncms you can have full control of your data, you can install it on your own server or you can use this hosted service.

Emoncms is licenced under the GPL Affero licence (AGPL)

# Developers
Emoncms is developed and has had contributions from the following people.

- Trystan Lea		https://github.com/trystanlea (principal maintainer)
- Ildefonso Martínez	https://github.com/ildemartinez
- Matthew Wire		https://github.com/mattwire
- Baptiste Gaultier	https://github.com/bgaultier
- Paul Allen		https://github.com/MarsFlyer
- James Moore		https://github.com/foozmeat		
- Lloyda		https://github.com/lloyda
- JSidrach		https://github.com/JSidrach
- Jramer		https://github.com/jramer
- Drsdre		https://github.com/drsdre
- Dexa187		https://github.com/dexa187
- Carlos Alonso Gabizó

## Upgrading from version 4.0 (Modular emoncms)

Download the latest version either by clicking on the zip icon in github or using git if you used git clone:

    $ git pull origin master
    
Make a copy of your current settings.php file and create a new settings.php file from default.settings.php. Enter your emoncms database settings.
Add the line:
 
    $updatelogin = true;
    
to settings.php to enable a special database update only session, be sure to remove this line from settings.php once complete.

In your internet browser goto open the admin/view page:

    http://localhost/emoncms/admin/view
    
Click on the database update and check button to launch the database update script. 
You should now see a list of changes to be performed on your existing emoncms database.
You may at this point want to backup your input and users table before applying the changes.
Once your happy with the changes click on apply changes to automatically apply all changes.

That should be it.

You may need to clear your cache if you find some of the interfaces buggy/missing.


### NGINX Configuration
To get the correct routing added when hosted with NGINX instead of Apache, add this to your config /etc/nginx/sites-enabled/default
You might need to adjust to your path if not running in the subdirectory /emoncms/
	server {
		location /emoncms/ {
			index index.php;
			try_files $uri $uri/ /emoncms/index.php?q=$uri&$args;
		}
	}