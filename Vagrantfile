# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|

    config.vm.box = "bento/ubuntu-14.04"
    config.vm.network "forwarded_port", guest: 80, host: 8080
    config.vm.synced_folder ".", "/var/www/html", :owner => "www-data", :group => "www-data"

    config.vm.provider "virtualbox" do |vb|
        vb.name = "emoncms"
        vb.cpus = 1
        vb.memory = "2048"
    end

    config.vm.provision "shell", inline: $script, keep_color: true
end

$script = <<SCRIPT
    export DEBIAN_FRONTEND=noninteractive
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password vagrant'
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password vagrant'

    sudo apt-get update

    sudo apt-get install -y php5 php5-xdebug php5-mysql apache2 mysql-server

    sudo printf "zend_extension=xdebug.so\ndebug.max_nesting_level = 400\nxdebug.remote_enable=on\nxdebug.remote_connect_back=on\nhtml_errors=1\nxdebug.extended_info=1" > /etc/php5/mods-available/xdebug.ini

    sudo a2enmod rewrite
    sudo a2enmod mime
    sudo a2enmod deflate
    sudo a2enmod filter

    cd /etc/apache2

    sudo sed -i 's/AllowOverride None/AllowOverride All/g' apache2.conf

    sudo service apache2 restart

    cd /var/www/html
    sudo rm index.html
    cp default.settings.php settings.php
    sed -i 's/_DB_USER_/root/g' settings.php
    sed -i 's/_DB_PASSWORD_/vagrant/g' settings.php

    mysql -uroot -pvagrant -e "CREATE DATABASE IF NOT EXISTS emoncms"
SCRIPT