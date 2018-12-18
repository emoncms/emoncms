# Feedwriter

The feedwriter service writes emoncms feed data to disk when redisbuffer is enabled in settings.php. Feed data is buffered and saved to disk in a more efficent use of block writes than would otherwise be the case, reducing disk wear. This is particularly important on systems running of SD cards.

## Removal of /etc/init.d script

    sudo service feedwriter stop
    sudo update-rc.d feedwriter disable
    sudo rm /etc/init.d/feedwriter

## Install feedwriter service

If you are not running EmonCMS on Raspbian, modify the .service file to run the service
as an appropriate user. The service is configured to run as the user 'pi' by default.
Install the service using the following commands:
```
sudo ln -s /var/www/emoncms/scripts/services/feedwriter/feedwriter.service /lib/systemd/system
sudo systemctl enable feedwriter.service
sudo systemctl start feedwriter.service
systemctl status feedwriter.service
```

View the log with:
`journalctl -f -u feedwriter`

Tested on Raspiban Stretch
