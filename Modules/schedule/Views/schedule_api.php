<?php global $path, $session, $user; ?>
<?php
    load_language_files(dirname(__DIR__).'/locale', "schedule_messages");
?>

<h2><?php echo ctx_tr('schedule_messages','Schedule API'); ?></h2>
<h3><?php echo ctx_tr('schedule_messages','Apikey authentication'); ?></h3>
<p><?php echo ctx_tr('schedule_messages','If you want to call any of the following actions when your not logged in you have this options to authenticate with the API key:'); ?></p>
<ul><li><?php echo ctx_tr('schedule_messages','Append on the URL of your request: &apikey=APIKEY'); ?></li>
<li><?php echo ctx_tr('schedule_messages','Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo ctx_tr('schedule_messages','Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li></ul>
<p><b><?php echo ctx_tr('schedule_messages','Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo ctx_tr('schedule_messages','Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo ctx_tr('schedule_messages','Available HTML URLs'); ?></h3>
<table class="table">
    <tr><td><?php echo ctx_tr('schedule_messages','The schedule list view'); ?></td><td><a href="<?php echo $path; ?>schedule/view"><?php echo $path; ?>schedule/view</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','This page'); ?></td><td><a href="<?php echo $path; ?>schedule/api"><?php echo $path; ?>schedule/api</a></td></tr>
</table>

<h3><?php echo ctx_tr('schedule_messages','Available JSON commands'); ?></h3>
<p><?php echo ctx_tr('schedule_messages','To use the json api the request url needs to include <b>.json</b>'); ?></p>

<p><b><?php echo ctx_tr('schedule_messages','Schedule process actions'); ?></b></p>
<table class="table">
    <tr><td><?php echo ctx_tr('schedule_messages','List schedules'); ?></td><td><a href="<?php echo $path; ?>schedule/list.json"><?php echo $path; ?>schedule/list.json</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Get schedule details'); ?></td><td><a href="<?php echo $path; ?>schedule/get.json?id=1"><?php echo $path; ?>schedule/get.json?id=1</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Get only the expression'); ?></td><td><a href="<?php echo $path; ?>schedule/expression.json?id=1"><?php echo $path; ?>schedule/expression.json?id=1</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Add a schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/create.json"><?php echo $path; ?>schedule/create.json</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Delete schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/delete.json?id=0"><?php echo $path; ?>schedule/delete.json?id=0</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Update schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/set.json?id=0&fields={%22expression%22:%22Mon-Fri|00:00-23:59%22}"><?php echo $path; ?>schedule/set.json?id=0&fields={"expression":"Mon-Fri|00:00-23:59"}</a></td></tr>
    <tr><td><?php echo ctx_tr('schedule_messages','Test the expression'); ?></td><td><a href="<?php echo $path; ?>schedule/test.json?id=1"><?php echo $path; ?>schedule/test.json?id=1</a></td></tr>
</table>
