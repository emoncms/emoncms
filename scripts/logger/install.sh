#! /bin/sh
# Run with sudo ./install

echo "Now setting up Logrotate..."
echo "Backing up old logrotate configuration..."
sudo mv /etc/logrotate.conf /home/pi/logrotate.conf.old
echo "Linked to new logrotate configuration..."
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate.conf /etc/logrotate.conf
sudo chown root /etc/logrotate.conf
echo "Backing up old logrotate cron job..."
sudo mv /etc/cron.daily/logrotate /home/pi/logrotate.old
echo "Linked to new logrotate cron job..."
sudo chmod a+x /var/www/html/emoncms/scripts/logger/logrotate
sudo ln -s /var/www/html/emoncms/scripts/logger/logrotate /etc/cron.daily/logrotate
sudo mv /etc/cron.daily/logrotate /etc/cron.hourly/logrotate
echo ""
echo "Completed"
echo ""

if [ ! -d /var/log/logrotate ]; then
  echo "setup logrotate state & logfile in /var/log/logrotate"
  sudo mkdir /var/log/logrotate
  sudo chown -R root:adm /var/log/logrotate
fi

echo ""
echo "Completed"
echo ""
