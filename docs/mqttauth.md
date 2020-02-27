# MQTT Auth Setup

This guide assumes that you have installed emoncms using the EmonScripts automated build script:

See: https://github.com/openenergymonitor/EmonScripts

## Install Mosquitto Auth Plugin

The mosquitto jpmens auth plugin enables authentication and access list control from an external database such as mysql or redis.

Download jpmens/mosquitto-auth-plug here:

- [https://github.com/jpmens/mosquitto-auth-plug](https://github.com/jpmens/mosquitto-auth-plug)

A useful guide on using the plugin: http://my-classes.com/2015/02/05/acl-mosquitto-mqtt-broker-auth-plugin/

#### Installation

Install dependencies (may not be needed?):

    sudo apt-get install libc-ares-dev libcurl4-openssl-dev libmysqlclient-dev uuid-dev
    
Get Mosquitto and build it.

    tar xvzf mosquitto-1.5.tar.gz
    cd mosquitto-1.5
    make mosquitto
    sudo make install
    
Get mosquitto-auth-plug source and create a suitable configuration file (works with mosquitto up to v1.5.9)

    git clone https://github.com/jpmens/mosquitto-auth-plug.git
    cd mosquitto-auth-plug
    cp config.mk.in config.mk
    make

#### Mosquitto configuration

mosquitto.conf config file:

    # Place your local configuration in /etc/mosquitto/conf.d/
    #
    # A full description of the configuration file is at
    # /usr/share/doc/mosquitto/examples/mosquitto.conf.example

    pid_file /var/run/mosquitto.pid
    persistence false
    persistence_location /var/lib/mosquitto/
    log_dest file /var/log/mosquitto/mosquitto.log
    include_dir /etc/mosquitto/conf.d

    allow_anonymous false
    # password_file /etc/mosquitto/passwd

    auth_plugin /home/username/mosquitto-auth-plug/auth-plug.so
    auth_opt_backends mysql
    auth_opt_host localhost
    auth_opt_port 3306
    auth_opt_dbname emoncms
    auth_opt_user -----
    auth_opt_pass -----
    auth_opt_userquery SELECT mqtthash FROM users WHERE username = '%s'
    auth_opt_superquery SELECT COUNT(*) FROM users WHERE username = '%s' AND super = 1
    auth_opt_aclquery SELECT topic FROM mqtt_acls WHERE (username = '%s') AND (rw >= %d)
    
### Run mosquitto

    sudo mosquitto -c /etc/mosquitto/mosquitto.conf
    
### View mosquitto log

    tail -f /var/log/mosquitto/mosquitto.log
    
### Emoncms setup

Install php pecl package mcrypt following guide here: 
[https://www.techrepublic.com/article/how-to-install-mcrypt-for-php-7-2/](https://www.techrepublic.com/article/how-to-install-mcrypt-for-php-7-2/)

Change MQTT basetopic

    nano /var/www/emoncms/settings.ini
    
The mqtt section should look like this, with your emoncms admin account username and password and changed basetopic:
    
    [mqtt]
    enabled = true
    user = 'admin'
    password = 'adminpassword'
    basetopic = 'user'
    
Enable multiuser emoncms:

    [interface]
    enable_multi_user = true
    
### Posting data to emoncms

Restart emoncms_mqtt:

    sudo service emoncms_mqtt restart
    
Post data on MQTT topic:

    topic: user/1/devicename/inputname 
    value: 100
