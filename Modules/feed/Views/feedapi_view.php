<?php 

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  global $path; 

?>

<h2>Feed API</h2>

<h3>General</h3>
<p><a href="<?php echo $path; ?>feed/create?name=test&type=0"><?php echo $path; ?>feed/create?name=test&type=0</a></p>
<p><a href="<?php echo $path; ?>feed/getid?name=test"><?php echo $path; ?>feed/getid?name=test</a></p>
<p><a href="<?php echo $path; ?>feed/list"><?php echo $path; ?>feed/list</a></p>

<h3>Feed properties</h3>
<p><a href="<?php echo $path; ?>feed/value?id=1"><?php echo $path; ?>feed/value?id=1</a></p>
<p><a href="<?php echo $path; ?>feed/get?id=1"><?php echo $path; ?>feed/get?id=1</a></p>
<p><a href="<?php echo $path; ?>feed/get?id=1&field=name"><?php echo $path; ?>feed/get?id=1&field=name</a></p>
<p><a href="<?php echo $path; ?>feed/set?id=1&field=name&value=newname"><?php echo $path; ?>feed/set?id=1&field=name&value=newname</a></p>

<h3>Feed data</h3>
<p><a href="<?php echo $path; ?>feed/insert?id=1&value=100"><?php echo $path; ?>feed/insert?id=1&value=100</a></p>
<p><?php echo $path; ?>feed/update?id=1&time=UNIXTIME&value=100</p>
<p><a href="<?php echo $path; ?>feed/data?id=1?start=0&end=0&dp=100"><?php echo $path; ?>feed/data?id=1?start=0&end=0&dp=100</a></p>

<h3>Delete feeds</h3>
<p><a href="<?php echo $path; ?>feed/delete?id=1"><?php echo $path; ?>feed/delete?id=1</a></p>
<p><a href="<?php echo $path; ?>feed/restore?id=1"><?php echo $path; ?>feed/restore?id=1</a></p>
<p><a href="<?php echo $path; ?>feed/emptybin"><?php echo $path; ?>feed/emptybin</a></p>
