<h2><?php echo _('Database setup, update and status check'); ?></h2>
<p><?php echo _('This page displays the output of the database setup and update process which checks the database requirements of each module installed and enter any new table or fields if required.'); ?></p>
<p><?php echo _('If all the item statuses below show ok that means your database is setup correctly.'); ?></p>
<br>

<table class="table" >
    <tr><th><?php echo _('Schema item'); ?></th><th><?php echo _('Name'); ?></th><th><?php echo _('Status'); ?></th></tr>
    <?php $i=0; foreach ($out as $line) { $i++; ?>

    <?php if ($line[0]=='Table') { ?>

    <tr class="d<?php echo ($i & 1); ?>" ><th><?php echo $line[0]; ?></th><th><?php echo $line[1]; ?></th><th><?php echo $line[2]; ?></th></tr>

    <?php } ?>

    <?php if ($line[0]=='field') { ?>

    <tr class="d<?php echo ($i & 1); ?>" ><td><i><?php echo $line[0]; ?></i></td><td><i><?php echo $line[1]; ?></i></td><td><i><?php echo $line[2]; ?></i></td></tr>

    <?php } ?>

    <?php } ?>
</table>
