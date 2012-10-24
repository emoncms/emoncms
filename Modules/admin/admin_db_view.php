<h2>Database setup, update and status check</h2>
<p>This page display's the output of the database setup and update process which checks the database requirements of each module installed and enter's any new table's or fields if required.</p>
<p>If all the item statuses below show ok that means your database is setup correctly.</p>
<br>

<table class="catlist" >
<tr><th>Schema item</th><th>Name</th><th>Status</th></tr>
<?php $i=0; foreach ($out as $line) { $i++; ?>

<?php if ($line[0]=='Table') { ?>

<tr class="d<?php echo ($i & 1); ?>" ><th><?php echo $line[0]; ?></th><th><?php echo $line[1]; ?></th><th><?php echo $line[2]; ?></th></tr>

<?php } ?>

<?php if ($line[0]=='field') { ?>

<tr class="d<?php echo ($i & 1); ?>" ><td><i><?php echo $line[0]; ?></i></td><td><i><?php echo $line[1]; ?></i></td><td><i><?php echo $line[2]; ?></i></td></tr>

<?php } ?>

<?php } ?>
</table>
