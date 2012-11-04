<h2><?php echo _("Users"); ?></h2>
<h3><?php echo sizeof($userlist); ?> <?php echo _("registered users"); ?></h3>
<table class='catlist'>
<tr><th>id</th><th><?php echo _("Username"); ?></th><th><?php echo _("Up hits"); ?></th><th><?php echo _("Dn hits"); ?></th><th><?php echo _("Admin"); ?></th></tr>

<?php $i = 0; foreach ($userlist as $user) { $i++; ?>
<?php if (!isset($user['uphits'])) $user['uphits'] = 0;
      if (!isset($user['dnhits'])) $user['dnhits'] = 0;
?>
  <tr class="<?php echo 'd' . ($i & 1); ?> " >
    <td><?php echo $user['userid']; ?></td>
    <td><?php echo $user['name']; ?></td>
    <td><?php echo $user['uphits']; ?></td>
    <td><?php echo $user['dnhits']; ?></td>
    <td><?php echo $user['admin']; ?></td>
  </tr>
<?php } ?>
</table>
