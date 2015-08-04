<?php global $path, $session, $user; ?>

<h2><?php echo _('Process API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.'); ?></p>
<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _('Available HTML URLs'); ?></h3>
<table class="table">
    <tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>process/api"><?php echo $path; ?>process/api</a></td></tr>
</table>

<h3><?php echo _('Available JSON commands'); ?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>

<br>
<p><b><?php echo _('Process actions'); ?></b></p>
<table class="table">
<tr><td><?php echo _('List all supported process'); ?></td><td><a href="<?php echo $path; ?>process/list.json"><?php echo $path; ?>process/list.json</a></td></tr>
</table>

