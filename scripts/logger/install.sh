#! /bin/sh
# Run with sudo ./install
echo "Installing rc.local to create"
echo "directory for emoncms log files"
echo "after a reboot..."
echo "Backing up old rc.local..."
sudo mv /etc/rc.local /var/www/html/emoncms/scripts/logger/rc.local.old
echo "Linked to new rc.local..."
sudo ln -s /var/www/html/emoncms/scripts/logger/rc.local /etc/rc.local
sudo chmod a+x /etc/rc.local
echo ""
echo "Completed"

echo "Now setting up Logrotate..."
echo "Backing up old logrotate configuration..."
sudo mv /etc/logrotate.conf /var/www/html/emoncms/scripts/logger/logrotate.conf.old
sudo chown root /var/www/html/emoncms/scripts/logger/logrotate.conf
echo "Linked to new logrotate configuration..."
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate.conf /etc/logrotate.conf
echo "Backing up old logrotate cron job..."
sudo mv /etc/cron.daily/logrotate /var/www/html/emoncms/scripts/logger/logrotate.old
echo "Linked to new logrotate cron job..."
sudo chmod a+x /var/www/html/emoncms/scripts/logger/logrotate
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate /etc/cron.daily/logrotate
echo ""
echo "Completed"
echo ""

echo "setup logrotate state & logfile in /var/log/logrotate"
sudo mkdir /var/log/logrotate
sudo chown -R root:adm /var/log/logrotate

echo ""
echo "Completed"
echo ""
