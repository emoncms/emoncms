## Windows Installation

#### 1) Install WAMP

[http://www.wampserver.com/en/](http://www.wampserver.com/en/)

![WAMP](files/wampserver.png)

Download the latest version (PHP4 but will work with PHP3). (~35Mb download). Phpmyadmin is also installed during automatically.

Before downloading the wamp installation file it will likely ask you to install the Microsoft Visual C++ 2010 SPI Redistributable Package (~5Mb download)

<br>

#### 2) Configure your server to accept mod rewrite

Emoncms makes use of htaccess mod_rewrite to create clean URL's while using the front controller and MVC architecture.

On Wampserver, enabling mod-rewrite is simply a case of left-click on your Wamp icon and hover on Apache, then hover over Apache Modules, then click PHP, then click on rewrite-module (you'll need to scroll down the list - it it's ticked, it's enabled). Do a restart all services on Wampserver.

#### 3) Enable gettext

The  "gettext" extension for Apache needs to be installed. Left-click on your Wamp icon and hover on PHP, then click on php.ini.  This will open the file in Notepad and you'll see the line about half-way way down (find "gettext"):
;extension=php_gettext.dll

it's commented out - remove the semi-colon and save the file. Do a restart all services on Wampserver.

#### 4) Create a MYSQL database

The easiest way to do this via a GUI is through a program called phpmyadmin. The default login is username root with no password. If you have a shared server you may need to do this through another mysql database setup program before you can access the database through phpmyadmin.

To create a database in phpmyadmin, click on Databases at the top, then enter 'emoncms' in the text input box and click create.

When, in phpmyadmin, the database has been created, a new user must also be created on host "localhost" (not "%") and have a password set.  When the user has been created, the user needs to have "all" privileges over the new database that has just been created (scroll down for Database-specific privileges). Those 4 items - the new user name, password, "localhost" and database name are the values that go into the settings.php file.

**Note:** this user isn't necessarily the same as one of the users who are allowed to register in emoncms once it's running.

#### 5) Download emoncms

**You may want to install one of the other branches of emoncms here, perhaps to try out a new feature set not yet available in the master branch. See the branch list and descriptions on the [start page](https://github.com/emoncms/emoncms)**

[https://github.com/emoncms/emoncms/archive/stable.zip](https://github.com/emoncms/emoncms/archive/stable.zip)


#### 6) Place emoncms in your WAMP public html / www directory

Left-click on your Wamp icon, in the list you should see a link to: www directory (or possibly public html on older installations).

Open the www directory from the wamp menu link.

The emoncms zip file is called emoncms-master.zip, unzip this directory and open the emoncms-master folder that it creates.
Inside the first emoncms-master directory is another emoncms-master directory, rename this second one to just emoncms.

Copy this second folder thats now called emoncms to your www directory.

#### 7) Create the emoncms data directory

Create a folder on your system for emoncms data to be saved in to, ie:

    C:\Users\Username\emoncmsdata

Inside this folder create 3 other folders: phpfiwa, phpfina and phptimeseries, these are the main feed engines used by emoncms.

#### 8) Set emoncms settings.php

Copy default.settings.php and rename to settings.php. Enter your database username, password, server and database name.

In the feedsettings section uncomment the datadir defenitions and set them to the location of each of the feed engine data folders on your system:

    'phpfiwa'=>array(
        'datadir'=>"C:\\Users\\Username\\emoncmsdata\\phpfiwa\\"
    ),
    'phpfina'=>array(
        'datadir'=>"C:\\Users\\Username\\emoncmsdata\\phpfina\\"
    ),
    'phptimeseries'=>array(
        'datadir'=>"C:\\Users\\Username\\emoncmsdata\\phptimeseries\\"
    )

On Windows '\' must be escaped with another '\' hence the '\\'

In the 'Other settings' section, change the $log_filename location to:

    $log_filename = dirname(FILE).'\\' . 'emoncms.log';

#### 9) Thats it! Open emoncms in your browser

[http://localhost/emoncms](http://localhost/emoncms)

Click on register and create a new user. It should now log you in and you will see the accounts page.

#### 10) Sending some data to emoncms

Click on the input tab and then *Input API Help*

Click on the example *Assign inputs to a node group* which will send 3 input values and assign them to node 1:

[http://localhost/emoncms/input/post.json?node=1&csv=100,200,300](http://localhost/emoncms/input/post.json?node=1&csv=100,200,300)

You should see 'ok' printed to the screen.

Navigate back to the inputs page and you will see 3 inputs listed under node 1.

Click on the wrench icon to bring up the input processing configuration page for a particular input.

Create a new *Log to feed* process and enter a name for the feed you'd like to create such as *test*

If you now repeat sending data via:

[http://localhost/emoncms/input/post.json?node=1&csv=100,200,300](http://localhost/emoncms/input/post.json?node=1&csv=100,200,300)

The data will now be being stored in a feed table.

After sending say 5-10 values. Navigate to *Feeds* and click on the eye button and zoom in to the last few minutes. You should see a line being drawn. If you dont see anything yet, keep sending data over a period of a couple of minutes and vary the input values.

### Using a jeelink to recieve data from wireless sensing nodes and forward to emoncms

If you have emoncms installed on your windows laptop or a windows home server with a usb port on it the easiest way to recieve data from sensor nodes is with a jeelink plugged into the usb port and then a python script on your computer or server forwarding the data straight to emoncms. Python installs nicely on windows and has a GUI editor that makes launching the python link script easier.

Download and install python (version 2.7) from here: [http://www.python.org/getit/](http://www.python.org/getit/)

and pyserial from here (version 2.7) [https://pypi.python.org/pypi/pyserial](https://pypi.python.org/pypi/pyserial)

Open the Python IDLE GUI (start menu) and then open the pylink.py code that's up on github here in the editor:
[https://github.com/emoncms/development/blob/master/Tutorials/Python/PyLink/pylink.py](https://github.com/emoncms/development/blob/master/Tutorials/Python/PyLink/pylink.py)

Enter your emoncms apikey in the script settings and then run to start recieving data.

For a more complete python gateway see the Jerome's oem_gateway here which can also be used

[https://github.com/Jerome-github/oem_gateway](https://github.com/Jerome-github/oem_gateway)

<div class='alert alert-info'>

<h3>Note: Browser Compatibility</h3>

<p><b>Chrome Ubuntu 23.0.1271.97</b> - developed with, works great.</p>

<p><b>Chrome Windows 25.0.1364.172</b> - quick check revealed no browser specific bugs.</p>

<p><b>Firefox Ubuntu 15.0.1</b> - no critical browser specific bugs, but movement in the dashboard editor is much less smooth than chrome.</p>

<p><b>Internet explorer 9</b> - works well with compatibility mode turned off. F12 Development tools -> browser mode: IE9. Some widgets such as the hot water cylinder do load later than the dial.</p>

<p><b>IE 8, 7</b> - not recommended, widgets and dashboard editor <b>do not work</b> due to no html5 canvas fix implemented but visualisations do work as these have a fix applied.</p>

</div>
