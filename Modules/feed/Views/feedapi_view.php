<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  global $user, $path, $session;

?>

<h2><?php echo _("Feed API");?></h2>

<h3><?php echo _("Apikey authentication");?></h3>
<p><?php echo _("If you want to call any of the following action's when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.");?></p>
<p><b><?php echo _("Read only:");?></b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>

<p><b><?php echo _("Read & Write:");?></b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _("Html");?></h3>
<p><a href="<?php echo $path; ?>feed/list"><?php echo $path; ?>feed/list</a> - <?php echo _("The feed list view");?></p>
<p><a href="<?php echo $path; ?>feed/api"><?php echo $path; ?>feed/api</a> - <?php echo _("This page");?></p>

<h3><?php echo _("JSON");?></h3>
<p><?php echo _("To use the json api the request url needs to include .json");?></p>
<p><a href="<?php echo $path; ?>feed/list.json?userid=0"><?php echo $path; ?>feed/list.json?userid=0</a> - <?php echo _("returns a list of public feeds made public by the given user.");?></p>
<p><a href="<?php echo $path; ?>feed/value.json?id=0"><?php echo $path; ?>feed/value.json?id=0</a> - <?php echo _("returns the present value of a given feed");?></p>
<p><a href="<?php echo $path; ?>feed/get.json?id=0&field="><?php echo $path; ?>feed/get.json?id=0&field=</a> - <?php echo _("returns the present value of a given feed");?></p>
<p><a href="<?php echo $path; ?>feed/data.json?id=0&start=&end=&dp="><?php echo $path; ?>feed/data.json?id=0&start=&end=&dp=</a> - <?php echo _("returns feed data");?></p>

<p><a href="<?php echo $path; ?>feed/getid.json?name="><?php echo $path; ?>feed/getid.json?name=</a> - <?php echo _("returns id of a feed given by name");?></p>
<p><a href="<?php echo $path; ?>feed/list.json"><?php echo $path; ?>feed/list.json</a></p>

<p><a href='<?php echo $path; ?>feed/create.json?name=Power&&engine=5&options={"interval":10}'><?php echo $path; ?>feed/create.json?name=Power&&engine=5&options={"interval":10}</a></p>

<p><a href="<?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}"><?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}</a></p>
<p><a href="<?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0</a></p>
<p><a href="<?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0</a></p>
<p><a href="<?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME"><?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME</a></p>
<p><a href="<?php echo $path; ?>feed/delete.json?id=0"><?php echo $path; ?>feed/delete.json?id=0</a></p>

