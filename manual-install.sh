# Running this script will set up emoncms in /usr/share/emoncms (and configure apache2 to serve it).
# However, it's important to note that if you have cloned the emoncms repo into /var/www/emoncms
# then you will need to move it to prevent conflicts.

# Important! This will remove your existing installation if you have one - although it will preserve
# a copy of your settings.php file.
mkdir -p /tmp/
cp /usr/share/emoncms-v8/www/settings.php /tmp/ || true

rm -rf /usr/share/emoncms-v8
mkdir  /usr/share/emoncms-v8

cp -r www /usr/share/emoncms-v8
cp conf/apache2/emoncms /etc/apache2/sites-available/emoncms-v8
cp /tmp/settings.php /usr/share/emoncms-v8/www/ || true
