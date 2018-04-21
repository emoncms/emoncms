#! /bin/sh
# Run with sudo ./install
echo "Installing rc.local to create"
echo "directory for emoncms log files"
echo "after a reboot..."
sudo mv /etc/rc.local /etc/old.rc.local
sudo ln -s /var/www/html/emoncms/scripts/logger/rc.local /etc/rc.local
sudo chmod a+x /etc/rc.local
echo ""
echo "Completed"
echo "Now setting up Logrotate..."
sudo mv /etc/logrotate.conf /etc/old.logrotate
sudo chown root /var/www/html/emoncms/scripts/logger/logrotate.conf
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate.conf /etc/logrotate.conf
sudo chmod a+x /var/www/html/emoncms/scripts/logger/logrotate
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate /etc/cron.hourly/logrotate
sudo mv /etc/cron.daily/logrotate /etc/cron.daily/old.logrotate 
sudo touch /etc/cron.daily/logrotate

echo "setup logrotate state & logfile in /var/log/logrotate"
sudo mkdir /var/log/logrotate
chown -R root:adm /var/log/logrotate

echo ""
echo "Completed"
echo ""
