<?php global $path, $session, $user; ?>

<h2><?php echo _('Schedule API'); ?></h2>
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
    <tr><td><?php echo _('The schedule list view'); ?></td><td><a href="<?php echo $path; ?>schedule/view"><?php echo $path; ?>schedule/view</a></td></tr>
    <tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>schedule/api"><?php echo $path; ?>schedule/api</a></td></tr>
</table>

<h3><?php echo _('Available JSON commands'); ?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>

<p><b><?php echo _('Schedule process actions'); ?></b></p>
<table class="table">
    <tr><td><?php echo _('List schedules'); ?></td><td><a href="<?php echo $path; ?>schedule/list.json"><?php echo $path; ?>schedule/list.json</a></td></tr>
    <tr><td><?php echo _('Get schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/get.json?id=1"><?php echo $path; ?>schedule/get.json?id=1</a></td></tr>
    <tr><td><?php echo _('Add a schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/create.json"><?php echo $path; ?>schedule/create.json</a></td></tr>
    <tr><td><?php echo _('Delete schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/delete.json?id=1"><?php echo $path; ?>schedule/delete.json?id=1</a></td></tr>
    <tr><td><?php echo _('Update schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/set.json?id=1&fields={%22expression%22:%22Mon-Fri%22}"><?php echo $path; ?>schedule/set.json?id=1&fields={"expression":"Mon-Fri"}</a></td></tr>
</table>

<h3><?php echo _('Expression documentation'); ?></h3>
<p><?php echo _('Expression is used to specify active range.'); ?></p>
<p><?php echo _('Granularity is day, month, week day, hour and minute. Expression is built mixing basic blocks with operation characters. All basic blocks are optional and can be mixed on the same expression to build complex schedule rules. White spaces are ignored and can be ommited.'); ?></p>
<p><?php echo _('Time must be in GMT timezone only.'); ?></p>
<p><b><?php echo _('Basic blocks:'); ?></b></p>
<pre>
                      <b>dd/mm</b> =>  Day and month in numeric format with leading zero
                      <b>hh:mm</b> =>  Hour in 24hrs format and minute with leading zero
<b>Mon Tue Wed Thu Fri Sat Sun</b> =>  Week day 3 letters english
</pre>
<p><b><?php echo _('Operation characters:'); ?></b></p>
<pre>
                         <b>-</b> => Range
                         <b>,</b> => Addition
                         <b>|</b> => Granularity separator
</pre>

<p><b><?php echo _('Expression examples:'); ?></b></p>
<pre>
'12:00-24:00'
'Mon-Fri | 00:00-24:00'
'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
'25/12 | 00:00-24:00'
'01/12 - 31/12 | Sat,Sun | 09:00-12:00, 13:00-20:00'
'15/01, 29/02, 01/01-18/02, 01/08-25/12, 19/09 | Mon-Fri | 12:00-14:00, 18:00-22:30, Thu | 18:00-22:00'
'00:00-08:00,22:00-24:00'                              <- Diary Winter Empty 
'08:00-09:00,10:30-18:00,20:30-22:00'                  <- Diary Winter Full
'09:00-10:30,18:00-20:30'                              <- Diary Winter Top 
    
'00:00-08:00,22:00-24:00'                              <- Diary Summer Empty
'08:00-10:30,13:00-19:30,21:00-22:00'                  <- Diary Summer Full
'10:30-13:00,19:30-21:00'                              <- Diary Summer Top
    
'Mon-Fri|00:00-07:00, Sat|00:00-09:30,13:00-18:30,22:00-24:00, Sun|00:00-24:00'    <- Weekly Winter Empty 
'Mon-Fri|07:00-09:30,12:00-18:30,21:00-24:00, Sat|09:30-13:00,18:30-22:00'         <- Weekly Winter Full
'Mon-Fri|09:30-12:00,18:30-21:00'                                                  <- Weekly Winter Top
    
'Mon-Fri|00:00-07:00, Sat|00:00-09:00,14:00-20:00,22:00-24:00, Sun|00:00-24:00'    <- Weekly Summer Empty 
'Mon-Fri|07:00-09:15,12:15-24:00, Sat|09:00-14:00,20:00-22:00'                     <- Weekly Summer Full
'Mon-Fri|09:15-12:15'                                                              <- Weekly Summer Top
</pre>
