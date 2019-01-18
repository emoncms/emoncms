# Service Runner

The service runner is used to trigger scrips (e.g update / backup) from emoncms, it needs to be running continuously. 

Service runner is a bridge between the web application and update bash scripts.

The process is as follows:

1. Web application triggers an update by setting a flag in redis
2. Service runner continuously polls redis for an update flag
3. Service runner starts the update and logs to a file which the web application reads

## Install python systemd service
**Check the old version has been uninstalled**
Install the service using the following commands:
```
sudo pip install redis
sudo ln -s /var/www/emoncms/scripts/services/service-runner/service-runner.service /lib/systemd/system
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

To check which service is installed check `crontab -l`.  if there is an entry pointing to the bash script it is running the earlier version.

To remove the old version (prior to installing the new version)
```
sudo crontab -e
```
Comment out the service-runner entry.
