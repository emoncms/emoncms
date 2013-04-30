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

<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.'); ?></p>
<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>

<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _("Html");?></h3>
<table class="table">
  <tr><td><?php echo _('The feed list view'); ?></td><td><a href="<?php echo $path; ?>feed/list"><?php echo $path; ?>feed/list</a></td></tr>
  <tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>feed/api"><?php echo $path; ?>feed/api</a></td></tr>
</table>

<h3><?php echo _("JSON");?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>
<table class="table">
  <tr><td><?php echo _('Returns a list of public feeds made public by the given user:'); ?></td><td><a href="<?php echo $path; ?>feed/list.json?userid=0"><?php echo $path; ?>feed/list.json?userid=0</a></td></tr>
  <tr><td><?php echo _('Returns the present value of a given feed:'); ?></td><td><a href="<?php echo $path; ?>feed/get.json?id=0&field="><?php echo $path; ?>feed/get.json?id=0&field=</a></td></tr>
  <tr><td><?php echo _('Returns the present value of a given feed:'); ?></td><td><a href="<?php echo $path; ?>feed/list.json?userid=0"><?php echo $path; ?>feed/list.json?userid=0</a></td></tr>
  <tr><td><?php echo _('Returns feed data:'); ?></td><td><a href="<?php echo $path; ?>feed/data.json?id=0&start=&end=&dp="><?php echo $path; ?>feed/data.json?id=0&start=&end=&dp=</a></td></tr>
  <tr><td><?php echo _('Returns histogram data:'); ?></td><td><a href="<?php echo $path; ?>feed/histogram.json?id=0&start=&end="><?php echo $path; ?>feed/histogram.json?id=0&start=&end=</a></td></tr>
  <tr><td><?php echo _('Returns kWh consumed in a given power band using histogram data type: '); ?></td><td><a href="<?php echo $path; ?>feed/kwhatpower.json?id=0&min=&max="><?php echo $path; ?>feed/kwhatpower.json?id=0&min=&max=</a></td></tr>
  <tr><td><?php echo _('Returns id of a feed given by name: '); ?></td><td><a href="<?php echo $path; ?>feed/getid.json?name="><?php echo $path; ?>feed/getid.json?name=</a></td></tr>
  <tr><td><?php echo _('Empty feed bin:'); ?></td><td><a href="<?php echo $path; ?>feed/emptybin.json"><?php echo $path; ?>feed/emptybin.json</a></td></tr>
  <tr><td><?php echo _('Set a feed field:'); ?></td><td><a href="<?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}"><?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}</a></td></tr>
  <tr><td><?php echo _('Insert a new datapoint:'); ?></td><td><a href="<?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0</a></td></tr>
  <tr><td><?php echo _('Update a datapoint:'); ?></td><td><a href="<?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0</a></td></tr>
  <tr><td><?php echo _('Delete a datapoint:'); ?></td><td><a href="<?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME"><?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME</a></td></tr>
  <tr><td><?php echo _('Delete a feed:'); ?></td><td><a href="<?php echo $path; ?>feed/delete.json?id=0"><?php echo $path; ?>feed/delete.json?id=0</a></td></tr>
  <tr><td><?php echo _('Restore a feed:'); ?></td><td><a href="<?php echo $path; ?>feed/restore.json?id=0"><?php echo $path; ?>feed/restore.json?id=0</a></td></tr>
  <tr><td><?php echo _('Export a feed:'); ?></td><td><a href="<?php echo $path; ?>feed/export.json?id=0&start=UNIXTIME"><?php echo $path; ?>feed/export.json?id=0&start=UNIXTIME</a></td></tr>
</table>
