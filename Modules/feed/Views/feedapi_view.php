<?php
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */
  defined('EMONCMS_EXEC') or die('Restricted access');
  global $user, $path, $session;
  
  $apikey_read = $user->get_apikey_read($session['userid']);
  $apikey_write = $user->get_apikey_write($session['userid']);

?>
<style>
.table td:nth-of-type(1) { width:40%;}
</style>

<h2><?php echo _("Feed API");?></h2>

<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when you\'re not logged in, you have the option to authenticate with the API key:'); ?></p>
<ul><li><?php echo _('Append to your request URL: &apikey=APIKEY'); ?></li>
<li><?php echo _('Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY" e.g. curl ').$path.'feed/value.json?id=1 -H "Authorization: Bearer '.$apikey_read.'"';?></li></ul>
<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $apikey_read; ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $apikey_write; ?>" />
</p>

<h3><?php echo _("Html");?></h3>
<p><a href="<?php echo $path; ?>feed/list"><?php echo $path; ?>feed/list</a> - <?php echo _("The feed list view");?></p>
<p><a href="<?php echo $path; ?>feed/api"><?php echo $path; ?>feed/api</a> - <?php echo _("This page");?></p>

<h3><?php echo _("JSON");?></h3>
<p><?php echo _("To use the json api the request url needs to include .json");?></p>


<p><b><?php echo _("Read feed actions");?></b></p>
<table class="table">
    <tr><td><?php echo _("List feeds for authenticated user"); ?></td><td>
		<a href="<?php echo $path; ?>feed/list.json"><?php echo $path; ?>feed/list.json</a>
	</td></tr>
    <tr><td><?php echo _("List public feeds for the given user"); ?></td><td>
		<a href="<?php echo $path; ?>feed/list.json?userid=0"><?php echo $path; ?>feed/list.json?userid=0</a>
	</td></tr>
    <tr><td><?php echo _("Get feed field");?></td><td>
		<a href="<?php echo $path; ?>feed/get.json?id=1&field=name"><?php echo $path; ?>feed/get.json?id=1&field=name</a>
	</td></tr>
    <tr><td><?php echo _("Get all feed fields");?></td><td>
		<a href="<?php echo $path; ?>feed/aget.json?id=1"><?php echo $path; ?>feed/aget.json?id=1</a>
	</td></tr>
	  <tr><td><?php echo _("Get feed meta (PHPFina)");?></td><td>
		<a href="<?php echo $path; ?>feed/getmeta.json?id=1"><?php echo $path; ?>feed/getmeta.json?id=1</a>
	</td></tr>
</table>

<p><b><?php echo _("Read feed data actions");?></b></p>
<table class="table">
	<tr><td><?php echo _("Last updated time and value for feed");?></td><td>
		<a href="<?php echo $path; ?>feed/timevalue.json?id=1"><?php echo $path; ?>feed/timevalue.json?id=1</a>
	</td></tr>
	<tr><td><?php echo _("Last value of a given feed");?></td><td>
		<a href="<?php echo $path; ?>feed/value.json?id=1"><?php echo $path; ?>feed/value.json?id=1</a>
	</td></tr>
  <tr><td><?php echo _("Fetch a value at a given time");?></td><td>
		<a href="<?php echo $path; ?>feed/value.json?id=1&time=UNIXTIME"><?php echo $path; ?>feed/value.json?id=1&time=UNIXTIME</a>
	</td></tr>
    <tr><td><?php echo _("Last value for multiple feeds");?></td><td>
		<a href="<?php echo $path; ?>feed/fetch.json?ids=1,2,3"><?php echo $path; ?>feed/fetch.json?ids=1,2,3</a>
	</td></tr>
    <tr><td><?php echo _("Returns feed data between start time and end time at the interval specified. If no data is present null values are returned.");?></td><td>
    <a href="<?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=10"><?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=10</a>
  </td></tr>
    <tr><td><?php echo _("Returns feed data between start time and end time at the interval specified. Each datapoint is the average (mean) for the period starting at the datapoint timestamp.");?></td><td>
    <a href="<?php echo $path; ?>feed/average.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=3600"><?php echo $path; ?>feed/average.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=3600</a>
  </td></tr>

    <tr><td><?php echo _("Returns feed datapoints at the start of each day aligned to user timezone set in the user profile. This is used by the bar graph and apps module to generate kWh per day from cumulative kWh data.");?></td><td>
    <a href="<?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&mode=daily"><?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&<b>mode=daily</b></a>
  </td></tr>
    <tr><td><?php echo _("Export CSV data (timeformat=1: provides date time string format)");?></td><td>
		<a href="<?php echo $path; ?>feed/csvexport.json?id=0&start=UNIXTIME&end=UNIXTIME&interval=60&timeformat=1"><?php echo $path; ?>feed/csvexport.json?id=0&start=UNIXTIME&end=UNIXTIME&interval=60&timeformat=1=</a>
	</td></tr>
	
</table>

<p><b><?php echo _("Write feed data actions");?></b></p>
<table class="table">
    <tr><td><?php echo _("Insert new data point");?></td><td>
		<a href="<?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0</a>
	</td></tr>
    <tr><td><?php echo _("Insert multiple data points");?></td><td>
    <?php $data = array(); for($i=0; $i<4; $i++) { $data[] = array(floor((time()+($i*10))*0.1)*10,100+50*$i); } ?> 
		<a href="<?php echo $path; ?>feed/insert.json?id=0&data=<?php echo json_encode($data); ?>"><?php echo $path; ?>feed/insert.json?id=0&data=<?php echo json_encode($data); ?></a>
	</td></tr>
    <tr><td><?php echo _("Update data point");?></td><td>
		<a href="<?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0</a>
	</td></tr>
    <tr><td><?php echo _("Delete data point");?></td><td>
		<a href="<?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME"><?php echo $path; ?>feed/deletedatapoint.json?id=0&feedtime=UNIXTIME</a>
	</td></tr>
</table>

<p><b><?php echo _("Feed setup actions");?></b></p>
<table class="table">
	<tr><td><?php echo _("Create new feed");?></td><td>
		<a href='<?php echo $path; ?>feed/create.json?tag=Test&name=Power&datatype=1&engine=5&options={"interval":10}'><?php echo $path; ?>feed/create.json?tag=Test&name=Power&datatype=1&engine=5&options={"interval":10}</a>
	</td></tr>
    <tr><td><?php echo _("Delete existent feed");?></td><td>
		<a href="<?php echo $path; ?>feed/delete.json?id=0"><?php echo $path; ?>feed/delete.json?id=0</a>
	</td></tr>
    <tr><td><?php echo _("Update feed field");?></td><td>
		<a href="<?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}"><?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}</a>
	</td></tr>
    <tr><td><?php echo _("Return total engines size");?></td><td>
		<a href="<?php echo $path; ?>feed/updatesize.json"><?php echo $path; ?>feed/updatesize.json</a>
	</td></tr>
    <tr><td><?php echo _("Return buffer points pending write");?></td><td>
		<a href="<?php echo $path; ?>feed/buffersize.json"><?php echo $path; ?>feed/buffersize.json</a>
	</td></tr>
</table>
	
<p><b><?php echo _('Virtual feed process actions'); ?></b></p>
<table class="table">
    <tr><td><?php echo _('Get feed process list'); ?></td><td><a href="<?php echo $path; ?>feed/process/get.json?id=1"><?php echo $path; ?>feed/process/get.json?id=1</a></td></tr>
    <tr><td><?php echo _('Set feed process list'); ?></td><td><a href="<?php echo $path; ?>feed/process/set.json?id=0&processlist=0:0"><?php echo $path; ?>feed/process/set.json?id=0&processlist=0:0</a></td></tr>
    <tr><td><?php echo _('Reset feed process list'); ?></td><td><a href="<?php echo $path; ?>feed/process/reset.json?id=0"><?php echo $path; ?>feed/process/reset.json?id=0</a></td></tr>
</table>
