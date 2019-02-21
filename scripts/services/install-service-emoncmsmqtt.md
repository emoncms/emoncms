# emoncms_mqtt

The emoncms_mqtt service is a replacement for the origin mqtt_input service.

## Removal of /etc/init.d script

    sudo service mqtt_input stop
    sudo update-rc.d mqtt_input disable
    sudo rm /etc/init.d/mqtt_input

## Install emoncms_mqtt service

Install the service using the following commands:
```
sudo ln -s /var/www/emoncms/scripts/services/emoncms_mqtt/emoncms_mqtt.service /lib/systemd/system
sudo systemctl enable emoncms_mqtt.service
sudo systemctl start emoncms_mqtt.service
systemctl status emoncms_mqtt.service
```

View the log with:
`journalctl -f -u emoncms_mqtt`

