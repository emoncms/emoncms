# Import / Backup

The [Emoncms backup module](https://github.com/emoncms/backup) can be used to backup and restore/import an emoncms installation and migrate from an older emonPi / emonBase image to the latest image.

There are two methods available to do this:

1. Import/Restore an emoncms installation directly without a previous backup archive using a USB SD card reader.
2. Export and import a compressed archive containing all emoncms user data.

The direct USB SD card reader approach is the most straightforward method for migration from an old SD card to a new SD card running the latest emonSD image. It can also minimise data loss in the event of a system failure where a recent archive backup has not been created.

The archive export/import approach is useful for creating backup snapshot's and can be a good fall back in the case of more serious SD card corruption. An archive export will however be needed prior to the failure point.

<p class="note">
Currently the Backup Module can only be used with Local Emoncms <strong>not</strong> Emoncms.org </p>

## Import using a USB SD card reader

### 1. Prepare a new card

It is a good idea to start with a new SD Card to minimise risk of disk errors from previous use, though reuse should also be fine if lightly used. A 16Gb card should suffice; emonCMS is very efficient in the way it stores it's data.

There are 2 options for a new card:

1. Purchase a new card with the image pre-installed, from the [OEM Store](https://shop.openenergymonitor.com/emonsd-pre-loaded-raspberry-pi-sd-card/).
2. Burn/flash a new image to an SD Card. To do this:
    1. Download image from the [Release Page](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log).
    2. The easiest method of flashing the new image to an SD card is to use a cross-platform tool called Etcher, see: [https://www.etcher.io/](https://www.etcher.io/)
    
### 2. Initial boot

1. Shutdown your existing system by clicking on Shutdown on the emonCMS Admin page, after 30s remove the USB power cable to fully power down.
2. Remove your existing SD card (you will need this SD card again in a moment).
3. Insert the new SD card & power up the device. Then wait, wait, wait, make a cup of coffee, wait, wait, wait… (lots of updates etc) - really do not rush this part it does take a while.
4. If you do not have a wired Ethernet connection you will need to [setup your WiFi](https://guide.openenergymonitor.org/setup/connect/#1a-connect-to-wifi). **Note** the updates will not happen until after you have connected the Pi to the Internet.

Once the initial update and setup is complete, you can proceed to import your data.

### 3. Restoring your system

1. Place the old SD card in an SD card reader and plug into any of the USB ports on the emonPi/emonBase running the new image.
2. From the emonCMS login page, click register and create a temporary user. Once the import is complete the original user details will be used.
3. Navigate to Setup > Backup
4. Click `Import from USB drive` to start import process
5. Once the import is complete, log out and back into the emonCMS page with the original user details

![USB Import](img/usb_import.png)

**Fixing a corrupt SD card**

After a system failure the SD card may become corrupted and will not mount when the USB importer is ran. It can be possible to restore a corrupted SD card by running fsck to fix the card errors. To do this: 

1\. Place the old SD card in a SD card reader and plug into any of the USB ports on the Pi running the new image<br>
2\. SSH into the emonPi/emonbase<br>
3\. Run the following commands (without part in brackets) to attempt to fix the card:<br>

<pre style="font-family:monospace; font-size:14px; background-color: #eee; padding: 20px;">
sudo fsck.ext4 /dev/sda2 (root OS partition)
sudo fsck.ext2 /dev/sda3 (data partition)
</pre>

4\. Continue as above, Navigate to Setup > Backup and click `Import from USB drive` to start import process

---

## Archive Export

1. Navigate to Setup > Backup
2. Click `Create Backup` (see screenshot below)
3. Wait for backup to be created, then refresh the page to view `Download Backup` link
4. Download `.tar.gz` compressed backup

![backup old data](img/export.png)

## Archive Import

<p class='note warning'>
Importing / restoring a backup will overwrite <strong>ALL</strong> data in the current Emoncms account.
</p>

*Note for emonSD-30Oct18.img.zip: If the image has been written to an SD card larger than 4GB the data partition should be expanded to fill the SD card to create sufficient space to import a backup. **Do not use Raspbian raspi-config**, instead connect via SSH and run `$ sudo emonSDexpand` and follow prompts.*

*The latest emonSD-17Oct19.img.zip has already been expanded to fit a minimum 16 GB SD card size. To expand the data partition further run: `/opt/emoncms/modules/usefulscripts/sdpart/./sdpart_imagefile`.*

To import a backup:

1. Check available disk space in the data partition (`/var/opt/emoncms`), see `Local Emoncms > Setup > Admin`
1. Select `.tar.gz` backup file
2. Wait for upload to complete
3. Click `Import Backup`
4. Check restore log (see below)
5. Log out then log back into Local Emocms using the imported account login credentials

<p class='note warning'>
Backup <b>tar.gz</b> filename cannot contain any spaces; e.g., if the same backup has been downloaded more than once: rename <b>'emoncms-backup-2016-04-23 (1).tar'</b> to <b>'emoncms-backup-2016-04-23.tar'</b> before uploading.
</p>

*`emonSDexpand` will run `~/usefulscripts/sdpart/./sdpart_imagefile` script, for more info see [Useful Scripts Readme](https://github.com/emoncms/usefulscripts#sdpart_imagefile)*

![Import](img/import.png)

### Successful import log example

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js" type="text/javascript"></script>
<script src="/javascripts/showHide.js" type="text/javascript"></script>
<script type="text/javascript">

$(document).ready(function(){


   $('.show_hide').showHide({
		speed: 100,  // speed you want the toggle to happen
		//easing: '',  // the animation effect you want. Remove this line if you dont want an effect and if you haven't included jQuery UI
		changeText: 0, // if you dont want the button text to change, set this to 0
		showText: 'View',// the button text to show when a div is closed
		hideText: 'Close' // the button text to show when a div is open

	});


});

</script>


<button type="button" class="show_hide" href="#" rel="#slidingDiv">View</button>
<div id="slidingDiv" class="toggleDiv" style="display: none;">

<pre>
=== Emoncms import start ===
2019-10-18-08:21:15
Backup module version:
cat: /opt/emoncms/modules/backup/backup/module.json: No such file or directory
EUID: 1000
Reading /opt/emoncms/modules/backup/config.cfg....
Location of data databases: /var/opt/emoncms
Location of emonhub.conf: /etc/emonhub
Location of Emoncms: /var/www/emoncms
Backup destination: /opt/openenergymonitor/data
Backup source path: /opt/openenergymonitor/data/uploads
Starting import from /opt/openenergymonitor/data/uploads to /opt/openenergymonitor/data...
Image version: emonSD-17Oct19
new image
Backup found: emoncms-backup-2019-10-18.tar.gz starting import..
Read MYSQL authentication details from settings.php
Decompressing backup..
emoncms.sql
emonhub.conf
settings.ini
phpfina/
phpfina/165119.meta
phpfina/165146.dat
phpfina/165117.meta
phpfina/165148.dat
phpfina/165073.dat
phpfina/165139.dat
phpfina/165107.meta
phpfina/165072.meta
phpfina/165106.dat
phpfina/165134.dat
phpfina/165152.meta
phpfina/165154.dat
phpfina/165106.meta
phpfina/165157.meta
phpfina/165076.dat
phpfina/165087.dat
phpfina/3.meta
phpfina/165080.meta
phpfina/165128.dat
phpfina/165102.dat
phpfina/1.meta
phpfina/165138.meta
phpfina/165085.dat
phpfina/165153.meta
phpfina/165089.dat
phpfina/165113.meta
phpfina/165159.meta
phpfina/165138.dat
phpfina/165079.dat
phpfina/165159.dat
phpfina/165094.dat
phpfina/2.dat
phpfina/165088.meta
phpfina/165135.dat
phpfina/3.dat
phpfina/165095.dat
phpfina/165073.meta
phpfina/165137.dat
phpfina/165124.dat
phpfina/165081.meta
phpfina/165156.dat
phpfina/165110.dat
phpfina/165122.dat
phpfina/165136.dat
phpfina/165080.dat
phpfina/165104.meta
phpfina/165122.meta
phpfina/165092.meta
phpfina/165147.meta
phpfina/165088.dat
phpfina/165077.dat
phpfina/165154.meta
phpfina/2.meta
phpfina/165092.dat
phpfina/165124.meta
phpfina/165072.dat
phpfina/165133.dat
phpfina/165082.meta
phpfina/165113.dat
phpfina/165105.dat
phpfina/165152.dat
phpfina/1.dat
phpfina/165118.meta
phpfina/165096.dat
phpfina/165083.dat
phpfina/165074.meta
phpfina/165158.dat
phpfina/165074.dat
phpfina/165136.meta
phpfina/165134.meta
phpfina/165119.dat
phpfina/165146.meta
phpfina/165107.dat
phpfina/165150.dat
phpfina/165084.meta
phpfina/165093.meta
phpfina/165127.dat
phpfina/165149.meta
phpfina/165096.meta
phpfina/165091.dat
phpfina/165137.meta
phpfina/165127.meta
phpfina/165081.dat
phpfina/165148.meta
phpfina/165125.dat
phpfina/165131.dat
phpfina/165112.dat
phpfina/165086.dat
phpfina/165145.dat
phpfina/165147.dat
phpfina/165110.meta
phpfina/165145.meta
phpfina/165104.dat
phpfina/165076.meta
phpfina/165084.dat
phpfina/165102.meta
phpfina/165155.meta
phpfina/165139.meta
phpfina/165121.dat
phpfina/165149.dat
phpfina/165079.meta
phpfina/165077.meta
phpfina/165071.dat
phpfina/165123.meta
phpfina/165120.meta
phpfina/165157.dat
phpfina/165075.dat
phpfina/165153.dat
phpfina/165123.dat
phpfina/165125.meta
phpfina/165150.meta
phpfina/165103.dat
phpfina/165089.meta
phpfina/165118.dat
phpfina/165093.dat
phpfina/165083.meta
phpfina/165133.meta
phpfina/165135.meta
phpfina/165078.meta
phpfina/165156.meta
phpfina/165075.meta
phpfina/165151.meta
phpfina/165112.meta
phpfina/165155.dat
phpfina/165078.dat
phpfina/165085.meta
phpfina/165130.meta
phpfina/165140.dat
phpfina/165111.meta
phpfina/165082.dat
phpfina/165128.meta
phpfina/165130.dat
phpfina/165087.meta
phpfina/165111.dat
phpfina/165121.meta
phpfina/165090.dat
phpfina/165151.dat
phpfina/165086.meta
phpfina/165090.meta
phpfina/165158.meta
phpfina/165091.meta
phpfina/165103.meta
phpfina/165071.meta
phpfina/165132.dat
phpfina/165131.meta
phpfina/165117.dat
phpfina/165105.meta
phpfina/165140.meta
phpfina/165120.dat
phpfina/165132.meta
phpfina/165095.meta
phpfina/165094.meta
phpfiwa/
phptimeseries/
Removing compressed backup to save disk space..
Stopping services..
Emoncms MYSQL database import...
Import feed meta data..
Restore phpfina and phptimeseries data folders...
Import emonhub.conf > /etc/emonhub/emohub.conf
OK
Restarting emonhub...
Restarting feedwriter...
2019-10-18-08:26:56
=== Emoncms import complete! ===
</pre>
</div>

---

## Included in backup

- Emoncms account credentials
- Historic Feed data
- Input Processing config
- Emoncms Dashboards
- Emoncms App settings
- EmonHub config: `emonhub.conf`

## Not included in backup

- WiFi passcode & custom network config
- Custom NodeRED flows
- Custom openHAB settings
- Input processing setup if migrating from Emoncms V8, input processing will need to be re-created after import and new inputs should be logged to imported feeds

## How-to backup items not automatically included

- nodeRED custom flows: select all flows then `menu > export > clipboard` copy the JSON text
- Connect via SSH:
  - See credentials for your image [emonSD download](../emonsd/download.md)
  - WiFi settings & password: backup copy: `~/data/wpa_supplicant.conf`
  - openHAB custom config: copy `~/data/open_openHab` folder

---

## Video Guide (Export/Import method)

The following video guide was put together using emoncms v9, the appearance will be different if you are using v10 of emoncms or newer but the functionality is much the same. We will be updating this video soon.

<div class='videoWrapper'>
<iframe width="560" height="315" src="https://www.youtube.com/embed/5U_tOlsWjXM" frameborder="0" allowfullscreen></iframe>
</div>

<br>

## Troubleshooting

If you have any questions or if an error occurs during the backup or import process please post in the [`Hardware > emonPi` category of the Community Forums](http://community.openenergymonitor.org/c/hardware/emonpi). Please provide as much information as possible e.g. backup / import logs and [emonSD version](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log).

Alternatively try and perform a manual import, see [Backup Module Readme](https://github.com/emoncms/backup).


## Export from an older emonPi / emonBase

If the Backup module is not visible in the Local Emoncms menu then the emonPi / emonBase is running an older version e.g Emoncms V8.x. Try the USB Import method above.

<p class="note">
To check what software stack (emonSD pre-built SD card) version an emonPi is running see instructions on emonPi <a href="https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log">emonSD download repository and changelog</a>
</p>
