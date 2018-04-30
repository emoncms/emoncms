<?php global $path, $session, $user; ?>
<?php
    $domain2 = "process_messages";
    bindtextdomain($domain2, "Modules/process/locale");
    bind_textdomain_codeset($domain2, 'UTF-8');
?>
<h2><?php echo dgettext('process_messages','Process API'); ?></h2>
<h3><?php echo dgettext('process_messages','Apikey authentication'); ?></h3>
<p><?php echo dgettext('process_messages','If you want to call any of the following actions when your not logged in you have this options to authenticate with the API key:'); ?></p>
<ul><li><?php echo dgettext('process_messages','Append on the URL of your request: &apikey=APIKEY'); ?></li>
<li><?php echo dgettext('process_messages','Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo dgettext('process_messages','Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li></ul>
<p><b><?php echo dgettext('process_messages','Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo dgettext('process_messages','Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo dgettext('process_messages','Available HTML URLs'); ?></h3>
<table class="table">
    <tr><td><?php echo dgettext('process_messages','This page'); ?></td><td><a href="<?php echo $path; ?>process/api"><?php echo $path; ?>process/api</a></td></tr>
</table>

<h3><?php echo dgettext('process_messages','Available JSON commands'); ?></h3>
<p><?php echo dgettext('process_messages','To use the json api the request url needs to include <b>.json</b>'); ?></p>

<br>
<p><b><?php echo dgettext('process_messages','Process actions'); ?></b></p>
<table class="table">
<tr><td><?php echo dgettext('process_messages','List all supported process'); ?></td><td><a href="<?php echo $path; ?>process/list.json"><?php echo $path; ?>process/list.json</a></td></tr>
</table>

