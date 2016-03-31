#! /bin/sh
# Run with sudo ./install
echo "Installing rc.local to create"
echo "directory for emoncms log files"
echo "after a reboot..."
sudo rm /etc/rc.local
sudo ln -s /var/www/emoncms/scripts/logger/rc.local /etc/rc.local
sudo chmod a+x /etc/rc.local
echo ""
echo "Completed"
echo "Now setting up Logrotate..."
sudo mv /etc/logrotate.conf /etc/logrotate.old
sudo chown root /var/www/emoncms/scripts/logger/logrotate.conf
sudo ln -s /var/www/emoncms/scripts/logger/logrotate.conf /etc/logrotate.conf
sudo chmod a+x /var/www/emoncms/scripts/logger/logrotate
sudo ln -s /var/www/emoncms/scripts/logger/logrotate /etc/cron.hourly/logrotate
sudo rm /etc/cron.daily/logrotate
sudo touch /etc/cron.daily/logrotate

echo "setup logrotate state & logfile in /var/log/logrotate"
sudo mkdir /var/log/logrotate
chown -R pi:pi /var/log/logrotate

echo ""
echo "Completed"
echo ""
