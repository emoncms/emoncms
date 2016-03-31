###Logger Scripts
####Contents:
+ rc.local - Script file containing startup instructions to create a directory structure and zero lengths files, necessary for MYSQL, Redis, Apache & Emoncms to be able to write log files to tmpfs (RAM).
+ logrotate - Shell script to load logrotate together with it's associated configuration file.
+ logrotate.conf - A configuration file for logrotate which specifies the criteria for log rotation.
+ install.sh - An installation script which;
  - removes the existing rc.local file, and symlinks the new rc.local file.
  - Renames existing logrotate.conf to logrotate.old
  - Sets permissions for, and symlinks the new logrotate.conf file.
  - Removes the existing logrotate file, and symlinks the new logrotate file to run hourly by Cron.

####Installation:
Update your emoncms installation to ensure that the necessary files are downloaded:

    cd /var/www/emoncms && git pull

Make the installation script executable:

    cd /var/www/emoncms/scripts/logger/ && sudo chmod +x install.sh

Run the installation script:

    sudo ./install.sh

Provided that the directory /var/log has been successfully mounted in tmpfs, a system reboot is necessary to complete the process.
