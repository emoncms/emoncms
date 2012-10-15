<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */
?>

  <h2><?php echo _("Notify "); ?><?php echo $feedid; ?></h2>
  <p>
    <?php echo _("Setup feed notifications"); ?>
  </p>
  <form class="well" action="../notify/set" method="get">
		<input type="hidden" name="feedid" value="<?php echo $feedid; ?>">    
      <?php echo _("Notify when feed = value: "); ?>
      <input class="input-small" type="text" name="onvalue" value="<?php echo $notify['onvalue']; ?>" />   
    <label class="checkbox">
    	<input type="checkbox" name="oninactive" value=1 <?php if ($notify['oninactive']) echo "checked" ?>/>
      <?php echo _("Notify when feed is inactive"); ?>
		</label>
    <label class="checkbox">
			<input type="checkbox" name="periodic" value=1 <?php if ($notify['periodic']) echo "checked" ?> />
    	<?php echo _("Notify feed status periodically"); ?>
    </label>
    <input type="submit" value="<?php echo _("Save"); ?>" class="button05"/>
  </form>
  <a href="../notify/settings"><?php echo _("Edit mail settings"); ?></a>

