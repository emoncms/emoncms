# Installation on Raspian/Debian/Ubuntu

Starting with version 8, it is possible to install emoncms using standard Debian package management. This installation path involves fewer
manual steps and controls for most dependency management automatically.

The Debian package is the most stable way of maintaining an emoncms installation because only formally tagged versions of the master branch are included in the [pkg-emoncms](https://github.com/Dave-McCraw/pkg-emoncms/) repository and uploaded to apt.

You may prefer to use the git appraoch if you wish to run the very latest version of emoncms and receive bug fixes as soon as they are pushed to git. The git appraoch can also be easier if you wish to develop and improve emoncms yourself.

**Installation instructions are maintained in the [pkg-emoncms](https://github.com/Dave-McCraw/pkg-emoncms/) readme**

## Configuring apt.sources

In order to access the OpenEnergyMonitor apt repository you need to add a line to your apt.sources configuration file, which is located at: 

    /etc/apt/sources.list

You need to add the following line:

    deb http://emon-repo.s3.amazonaws.com wheezy unstable

## Install emoncms

You will need to update your system repositories:

   sudo apt-get update

And then install emoncms (all dependencies will also be intalled at this point):

    sudo apt-get install emoncms

The Debian package manager will now ask you a series of questions to configure emoncms. These are used to generate a valid settings.php file
for your installation.

Once the process completes, you need to enable emoncms in Apache:

    sudo a2ensite emoncms

Now is also a good time to ensure that mod_rewrite is also running:

    sudo a2enmod rewrite

Now restart Apache:

    sudo /etc/init.d/apache2 restart

## Install PECL modules (serial, redis and swift mailer)

These modules are optional but will enhance the functionality of emoncms: serial is required to use the rfm12pi module, while redis will greatly reduce disk I/O (especially useful if you're running from an SD card). Swift mailer provides email :)

For instructions, see the general Linux installation steps below.

## Install add-on emoncms modules

You don't need to install all (or indeed any) of the optional add-on modules. 

If you do wish to do so, the easiest way is to clone them into the Modules directory. As Debian packages are uploaded to apt for each module, this advice will be updated.

    cd /usr/share/emoncms/www/Modules

    git clone https://github.com/emoncms/raspberrypi.git
    git clone https://github.com/emoncms/event.git
    git clone https://github.com/emoncms/openbem.git
    git clone https://github.com/emoncms/energy.git
    git clone https://github.com/emoncms/notify.git
    git clone https://github.com/emoncms/report.git
    git clone https://github.com/emoncms/packetgen.git
    git clone https://github.com/elyobelyob/mqtt.git

See individual module readme's for further information on individual module installation.

### In an internet browser, load emoncms:

[http://localhost/emoncms](http://localhost/emoncms)

The first time you run emoncms it will automatically setup the database and you will be taken straight to the register/login screen.

Create an account by entering your email and password and clicking register to complete.
