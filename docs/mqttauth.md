# MQTT Auth Setup

## Install Mosquitto Auth Plugin

The mosquitto jpmens auth plugin enables authentication and access list control from an external database such as mysql or redis.

Download jpmens/mosquitto-auth-plug here:

- [https://github.com/jpmens/mosquitto-auth-plug](https://github.com/jpmens/mosquitto-auth-plug)

A useful guide on using the plugin: http://my-classes.com/2015/02/05/acl-mosquitto-mqtt-broker-auth-plugin/

#### Installation

Install dependencies:

    sudo apt-get install libc-ares-dev libcurl4-openssl-dev libmysqlclient-dev uuid-dev
    
Get Mosquitto and build it

    tar xvzf mosquitto-1.5.tar.gz
    cd mosquitto-1.5
    make mosquitto
    sudo make install
    
Get mosquitto-auth-plug source and create a suitable configuration file

    git clone https://github.com/jpmens/mosquitto-auth-plug.git
    cd mosquitto-auth-plug
    cp config.mk.in config.mk
    make

The following steps may no longer be needed?
    
Fix for compile error:

- https://github.com/jpmens/mosquitto-auth-plug/issues/183

Recompile both mosquitto and auth plugin with option changed as detailed here:

- [https://github.com/jpmens/mosquitto-auth-plug/issues/33](https://github.com/jpmens/mosquitto-auth-plug/issues/33)

    nano mosquitto-1.4.10/config.mk
    Set: WITH_SRV:=no

Run: make clean, make, sudo make install in both.

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
