<?php global $path, $session, $user; ?>
<style>
  a.anchor{display: block; position: relative; top: -50px; visibility: hidden;}
</style>

<h2><?php echo _('Schedule API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in you have this options to authenticate with the API key:'); ?></p>
<ul><li><?php echo _('Append on the URL of your request: &apikey=APIKEY'); ?></li>
<li><?php echo _('Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li></ul>
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
    <tr><td><?php echo _('Get schedule details'); ?></td><td><a href="<?php echo $path; ?>schedule/get.json?id=1"><?php echo $path; ?>schedule/get.json?id=1</a></td></tr>
    <tr><td><?php echo _('Get only the expression'); ?></td><td><a href="<?php echo $path; ?>schedule/expression.json?id=1"><?php echo $path; ?>schedule/expression.json?id=1</a></td></tr>
    <tr><td><?php echo _('Add a schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/create.json"><?php echo $path; ?>schedule/create.json</a></td></tr>
    <tr><td><?php echo _('Delete schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/delete.json?id=0"><?php echo $path; ?>schedule/delete.json?id=0</a></td></tr>
    <tr><td><?php echo _('Update schedule'); ?></td><td><a href="<?php echo $path; ?>schedule/set.json?id=0&fields={%22expression%22:%22Mon-Fri|00:00-23:59%22}"><?php echo $path; ?>schedule/set.json?id=0&fields={"expression":"Mon-Fri|00:00-23:59"}</a></td></tr>
    <tr><td><?php echo _('Test the expression'); ?></td><td><a href="<?php echo $path; ?>schedule/test.json?id=1"><?php echo $path; ?>schedule/test.json?id=1</a></td></tr>
</table>

<a class="anchor" id="expression"></a> 
<h3><?php echo _('Expression documentation'); ?></h3>
<p><?php echo _('Expression is used to specify active range.'); ?></p>
<p><?php echo _('Granularity is day light saving time, month, day, week day, hour and minute.'); ?></p>
<p><?php echo _('Expression is built mixing basic blocks with operation characters. An hour is always required. All other basic blocks are optional and can be mixed on the same expression to build complex schedule rules. Ranges must be ordered older-newer. White spaces are ignored and can be ommited.'); ?></p>
<p><?php echo _('Timezone of expression is the same of the user account that created or edited it. If the expression is public, timezones conversions are automatic taken in account between owner and user.'); ?></p>
<p><b><?php echo _('Basic blocks:'); ?></b></p>
<pre>
           <b>Summer</b> or <b>Winter</b> =>  Day light saving time period
                      <b>mm/dd</b> =>  Month and day in numeric format with leading zero
<b>Mon Tue Wed Thu Fri Sat Sun</b> =>  Week day 3 letters english
                      <b>hh:mm</b> =>  Hour in 24hrs format and minute with leading zero
</pre>
<p><b><?php echo _('Operation characters:'); ?></b></p>
<pre>
                         <b>-</b> => Range
                         <b>,</b> => Addition
                         <b>|</b> => Granularity separator
</pre>

<p><b><?php echo _('Expression examples:'); ?></b></p>
<pre>
'12:00-23:59'
'Mon-Fri | 00:00-23:59'
'Summer | Mon-Fri | 00:00-23:59'
'Winter | Mon-Fri | 00:00-23:59'
'Winter | Mon-Fri | 09:00-09:59, Summer | Mon-Fri | 08:00-08:59'
'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
'12/25 | 00:00-23:59'
'12/01 - 12/31 | Sat,Sun | 09:00-11:59, 13:00-19:59'
'01/15, 02/29, 01/01-02/18, 08/01-12/25, 09/19 | Mon-Fri | 12:00-14:14, 18:00-22:29, Thu | 18:00-22:44'

'Mon-Fri|00:00-06:59, Sat|00:00-09:29,13:00-18:29,22:00-23:59, Sun|00:00-23:59'    <- Weekly Winter Empty 
'Mon-Fri|07:00-09:29,12:00-18:29,21:00-23:59, Sat|09:30-12:59,18:30-21:59'         <- Weekly Winter Full
'Mon-Fri|09:30-11:59,18:30-20:59'                                                  <- Weekly Winter Top
       
'Mon-Fri|00:00-06:59, Sat|00:00-08:59,14:00-19:59,22:00-23:59, Sun|00:00-23:59'    <- Weekly Summer Empty 
'Mon-Fri|07:00-09:14,12:15-23:59, Sat|09:00-13:59,20:00-21:59'                     <- Weekly Summer Full
'Mon-Fri|09:15-12:14'                                                              <- Weekly Summer Top
</pre>
