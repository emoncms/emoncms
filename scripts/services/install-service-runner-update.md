# Service Runner

The service runner is used to trigger scrips (e.g update / backup) from emoncms, it needs to be running continuously. 

Service runner is a bridge between the web application and update bash scripts.

The process is as follows:

1. Web application triggers an update by setting a flag in redis
2. Service runner continuously polls redis for an update flag
3. Service runner starts the update and logs to a file which the web application reads

## Install python systemd service

If you are not running EmonCMS on Raspbian, modify the .service file to run the service
as an appropriate user. The service is configured to run as the user 'pi' by default.
Install the service using the following commands:
```
sudo pip install redis
sudo ln -s /var/www/emoncms/scripts/services/service-runner/service-runner.service /lib/systemd/system
sudo systemctl daemon-reload
sudo systemctl enable service-runner.service
sudo systemctl start service-runner.service
systemctl status service-runner.service
```

View the log with:
`journalctl -f -u service-runner`

Tested on Raspiban Stretch

Prior to September 2018 the service runner ran as a bash script triggered by cron. The
bash script had to connect to redis every iteration of the loop which on a RPi 3 caused
service runner to consume 100% of the CPU.
This version was written by @greeebs using python and systemd instead of bash and cron, see
https://github.com/emoncms/emoncms/pull/1025 for the discussion.
The python service is far more efficient as a constant connection to redis can be kept open.
