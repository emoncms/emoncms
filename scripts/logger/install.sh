#! /bin/sh

# NOTE - Since this is now called as part of the update routine, 
# it needs to be safe to be called multiple times.
# TODO - review the use of sudo when the comment below states . . 
# Run with sudo ./install

echo "Now setting up Logrotate..."

# Only backup real conf files not symlinks
if [ -f /etc/logrotate.conf ]; then
    echo "Backing up old logrotate configuration..."
    sudo mv /etc/logrotate.conf /home/pi/logrotate.conf.old
fi
echo "Linking to emonsd logrotate configuration..."
sudo ln -sf /var/www/html/emoncms/scripts/logger/logrotate.conf /etc/logrotate.conf
sudo chown root:root /var/www/html/emoncms/scripts/logger/logrotate.conf

# Only backup real cron files not symlinks
if [ ! -f /etc/cron.daily/logrotate ]; then
    echo "Backing up old daily logrotate cron job..."
    sudo mv /etc/cron.daily/logrotate /home/pi/logrotate.daily.old
fi
if [ ! -f /etc/cron.hourly/logrotate ]; then
    echo "Backing up old hourly logrotate cron job..."
    sudo mv /etc/cron.hourly/logrotate /home/pi/logrotate.hourly.old
fi
echo "Linking to emonsd logrotate cron job..."
sudo chmod a+x /var/www/html/emoncms/scripts/logger/logrotate
sudo ln -sf /var/www/html/emoncms/scripts/logger/logrotate /etc/cron.hourly/logrotate

if [ ! -d /var/log/logrotate ]; then
    echo "setup /var/log/logrotate folder for logrotate.state & logrotate.log"
    sudo mkdir /var/log/logrotate
fi

# correct the ownership (regardless of whether it previously existed)
sudo chown -R root:adm /var/log/logrotate

echo ""
echo "Completed"
echo ""
